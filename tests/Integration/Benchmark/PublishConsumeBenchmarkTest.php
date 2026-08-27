<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration\Benchmark;

use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\IntegrationTestMessage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * EA-8268: measures publish -> consume behaviour to compare the CloudAMQP fork's Khepri/Leveled
 * storage against the old Mnesia-backed plugin. Informational only (see phpunit-benchmark
 * composer script / the non-blocking "benchmark" CI job) - no pass/fail threshold yet, there is
 * no established baseline to regress against. Only real errors fail this test.
 *
 * For delayed scenarios the reported latency is the SCHEDULING ERROR, i.e. how much later than
 * the requested delay the message actually arrived. Reporting the absolute round trip instead
 * would bury the interesting number: a 2s delay shows up as ~2040ms, where the part that
 * actually differs between the two plugins is the ~40ms on top.
 *
 * Iterations default to 200 and can be raised via BENCHMARK_ITERATIONS for an on-demand soak
 * run - more samples mainly sharpen the tail estimate, they do not cancel out runner jitter, so
 * the default stays small enough to run on every push.
 */
#[Group('benchmark')]
#[TestDox('Publish/consume benchmark (informational, non-blocking)')]
final class PublishConsumeBenchmarkTest extends IntegrationTestCase
{
    private const DEFAULT_ITERATIONS = 200;

    #[TestDox('undelayed round trips: baseline broker and client overhead')]
    public function testUndelayedPublishConsumeLatency(): void
    {
        $this->runBenchmark(
            'undelayed',
            'Direct publish/consume, no delay - baseline broker+client overhead',
            delaySeconds: 0,
        );
    }

    #[TestDox('delayed round trips: scheduling accuracy of the delayed-message exchange')]
    public function testDelayedPublishConsumeLatency(): void
    {
        $this->runBenchmark(
            'delayed-2s',
            'Publish/consume via the x-delayed-message exchange with a 2s delay - reported '
                . 'latency is the overshoot beyond the requested delay, i.e. the plugin\'s own '
                . 'scheduling overhead (Khepri/Leveled vs. the old Mnesia store)',
            delaySeconds: 2,
        );
    }

    private function iterations(): int
    {
        $configured = (int)(getenv('BENCHMARK_ITERATIONS') ?: 0);

        return $configured > 0 ? $configured : self::DEFAULT_ITERATIONS;
    }

    private function runBenchmark(string $label, string $description, int $delaySeconds): void
    {
        $iterations = $this->iterations();
        $routingKey = IntegrationTestMessage::getRoutingKey();
        $this->purgeQueuesFor($routingKey);

        $transport = $this->createTransport([IntegrationTestMessage::class]);

        /** @var array<int, float> $latencies */
        $latencies = [];
        /** @var array<string, float> $publishedAt */
        $publishedAt = [];
        $firstDeliveryAt = null;
        $lastDeliveryAt = null;

        $handler = function (IntegrationTestMessage $received) use (
            &$latencies,
            &$publishedAt,
            &$firstDeliveryAt,
            &$lastDeliveryAt,
            $delaySeconds
        ): void {
            $sentAt = $publishedAt[$received->getPayload()] ?? null;
            if ($sentAt === null) {
                return;
            }

            $now = microtime(true);
            // Subtract the requested delay so delayed and undelayed runs report the same thing:
            // the overhead the broker and plugin add on top of what was asked for.
            $latencies[] = ($now - $sentAt) - $delaySeconds;
            $firstDeliveryAt ??= $now;
            $lastDeliveryAt = $now;
        };

        $this->subscribe($transport, $this->subscriptionFor($routingKey), $handler);

        $publishStartedAt = microtime(true);
        for ($i = 0; $i < $iterations; ++$i) {
            $payload = "iteration-{$i}";
            $publishedAt[$payload] = microtime(true);
            $transport->publish(new IntegrationTestMessage(payload: $payload, delay: $delaySeconds), $delaySeconds);
        }
        $publishDuration = microtime(true) - $publishStartedAt;

        // Scale the drain window with the batch size so a soak run does not get cut short.
        $this->pollFor($transport, $delaySeconds + 30.0 + ($iterations * 0.05));

        self::assertNotEmpty($latencies, 'no messages were delivered - cannot benchmark');

        $this->writeReport($label, $description, $iterations, $latencies, [
            'publish_duration' => $publishDuration,
            'consume_duration' => ($lastDeliveryAt ?? 0.0) - ($firstDeliveryAt ?? 0.0),
        ]);
    }

    /**
     * @param array<int, float> $latencies overhead per delivered message, in seconds
     * @param array{publish_duration: float, consume_duration: float} $durations
     */
    private function writeReport(
        string $label,
        string $description,
        int $iterations,
        array $latencies,
        array $durations,
    ): void {
        sort($latencies);
        $count = count($latencies);
        $toMs = static fn (float $seconds): float => round($seconds * 1000, 2);
        $percentileMs = static fn (float $p): float => $toMs(
            $latencies[(int)min($count - 1, floor($p * $count))],
        );

        // matrix.broker.name / matrix.php-version from the workflow, so a report read on its own
        // (e.g. from the uploaded artifact) still says which leg produced it.
        $broker = getenv('BENCHMARK_BROKER') ?: 'unknown';
        $phpVersion = getenv('BENCHMARK_PHP_VERSION') ?: PHP_VERSION;

        $latencyMs = [
            'min' => $toMs($latencies[0]),
            'p50' => $percentileMs(0.5),
            'p95' => $percentileMs(0.95),
            'max' => $toMs($latencies[$count - 1]),
        ];
        // p99 only becomes meaningful once there are enough samples to have a hundredth.
        if ($count >= 100) {
            $latencyMs['p99'] = $percentileMs(0.99);
        }

        $publishRate = $durations['publish_duration'] > 0.0
            ? round($iterations / $durations['publish_duration'], 1)
            : null;
        $consumeRate = $durations['consume_duration'] > 0.0
            ? round($count / $durations['consume_duration'], 1)
            : null;

        $report = [
            'scenario' => $label,
            'description' => $description,
            'metric' => 'latency_ms is the overhead on top of the requested delay (publish -> consume, minus delay)',
            'broker' => $broker,
            'php_version' => $phpVersion,
            'iterations' => $iterations,
            'delivered' => $count,
            'latency_ms' => $latencyMs,
            'throughput_msg_per_s' => [
                'publish' => $publishRate,
                'consume' => $consumeRate,
            ],
        ];

        $buildDir = dirname(__DIR__, 3) . '/build';
        if (!is_dir($buildDir)) {
            mkdir($buildDir, 0777, true);
        }

        $path = $buildDir . '/benchmark-' . $label . '.json';
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT) . "\n");

        fwrite(STDOUT, sprintf(
            "\n[benchmark] %s (broker=%s, php=%s)\n"
                . "  %s\n"
                . "  %d/%d delivered - overhead beyond requested delay: min=%.2fms  p50=%.2fms  p95=%.2fms%s  max=%.2fms\n"
                . "  throughput: publish=%s msg/s  consume=%s msg/s\n"
                . "  full report: %s\n",
            $label,
            $broker,
            $phpVersion,
            $description,
            $count,
            $iterations,
            $latencyMs['min'],
            $latencyMs['p50'],
            $latencyMs['p95'],
            isset($latencyMs['p99']) ? sprintf('  p99=%.2fms', $latencyMs['p99']) : '',
            $latencyMs['max'],
            $publishRate === null ? 'n/a' : (string)$publishRate,
            $consumeRate === null ? 'n/a' : (string)$consumeRate,
            $path,
        ));
    }
}
