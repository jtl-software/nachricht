<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration\Benchmark;

use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\IntegrationTestMessage;
use JTL\Nachricht\Transport\Amqp\AmqpTransport;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * EA-8268: measures publish -> consume latency to compare the CloudAMQP fork's Khepri/Leveled
 * storage against the old Mnesia-backed plugin. Informational only (see phpunit-benchmark
 * composer script / the non-blocking "benchmark" CI job) - no pass/fail threshold yet, there is
 * no established baseline to regress against. Only real errors fail this test.
 */
#[Group('benchmark')]
#[TestDox('Publish/consume latency benchmark (informational, non-blocking)')]
final class PublishConsumeBenchmarkTest extends IntegrationTestCase
{
    private const ITERATIONS = 200;

    #[TestDox('measures latency for 200 undelayed publish/consume round trips')]
    public function testUndelayedPublishConsumeLatency(): void
    {
        $this->runBenchmark(
            'undelayed',
            'Direct publish/consume, no delay - baseline broker+client overhead',
            delaySeconds: 0,
        );
    }

    #[TestDox('measures latency for 200 round trips through the delayed exchange (2s delay)')]
    public function testDelayedPublishConsumeLatency(): void
    {
        $this->runBenchmark(
            'delayed-2s',
            'Publish/consume via the x-delayed-message exchange, 2s delay - isolates the '
                . 'delayed-exchange plugin\'s own overhead (Khepri/Leveled vs. the old Mnesia store)',
            delaySeconds: 2,
        );
    }

    private function runBenchmark(string $label, string $description, int $delaySeconds): void
    {
        $routingKey = IntegrationTestMessage::getRoutingKey();
        $this->purgeQueuesFor($routingKey);

        $transport = $this->createTransport([IntegrationTestMessage::class]);

        /** @var array<int, float> $latencies */
        $latencies = [];
        /** @var array<string, float> $publishedAt */
        $publishedAt = [];

        $handler = function (IntegrationTestMessage $received) use (&$latencies, &$publishedAt): void {
            $sentAt = $publishedAt[$received->getPayload()] ?? null;
            if ($sentAt !== null) {
                $latencies[] = microtime(true) - $sentAt;
            }
        };

        $this->subscribe($transport, $this->subscriptionFor($routingKey), $handler);

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $payload = "iteration-{$i}";
            $publishedAt[$payload] = microtime(true);
            $transport->publish(new IntegrationTestMessage(payload: $payload, delay: $delaySeconds), $delaySeconds);
        }

        $this->pollFor($transport, $delaySeconds + 30.0);

        self::assertNotEmpty($latencies, 'no messages were delivered - cannot benchmark');

        $this->writeReport($label, $description, $latencies);
    }

    /**
     * @param array<int, float> $latencies latency per delivered message, in seconds
     */
    private function writeReport(string $label, string $description, array $latencies): void
    {
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

        $report = [
            'scenario' => $label,
            'description' => $description,
            'broker' => $broker,
            'php_version' => $phpVersion,
            'iterations' => self::ITERATIONS,
            'delivered' => $count,
            'latency_ms' => [
                'min' => $toMs($latencies[0]),
                'p50' => $percentileMs(0.5),
                'p95' => $percentileMs(0.95),
                'max' => $toMs($latencies[$count - 1]),
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
                . "  %d/%d messages delivered - latency: min=%.2fms  p50=%.2fms  p95=%.2fms  max=%.2fms\n"
                . "  full report: %s\n",
            $label,
            $broker,
            $phpVersion,
            $description,
            $count,
            self::ITERATIONS,
            $report['latency_ms']['min'],
            $report['latency_ms']['p50'],
            $report['latency_ms']['p95'],
            $report['latency_ms']['max'],
            $path,
        ));
    }
}
