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
        $this->runBenchmark('undelayed', delaySeconds: 0);
    }

    #[TestDox('measures latency for 200 round trips through the delayed exchange (2s delay)')]
    public function testDelayedPublishConsumeLatency(): void
    {
        $this->runBenchmark('delayed-2s', delaySeconds: 2);
    }

    private function runBenchmark(string $label, int $delaySeconds): void
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

        $this->writeReport($label, $latencies);
    }

    /**
     * @param array<int, float> $latencies
     */
    private function writeReport(string $label, array $latencies): void
    {
        sort($latencies);
        $count = count($latencies);
        $percentile = static fn (float $p): float => $latencies[(int)min($count - 1, floor($p * $count))];

        $report = [
            'label' => $label,
            'iterations' => self::ITERATIONS,
            'delivered' => $count,
            'min' => $latencies[0],
            'p50' => $percentile(0.5),
            'p95' => $percentile(0.95),
            'max' => $latencies[$count - 1],
        ];

        $buildDir = dirname(__DIR__, 3) . '/build';
        if (!is_dir($buildDir)) {
            mkdir($buildDir, 0777, true);
        }

        $path = $buildDir . '/benchmark-' . $label . '.json';
        file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT) . "\n");

        fwrite(STDOUT, sprintf(
            "[benchmark:%s] delivered=%d min=%.3fs p50=%.3fs p95=%.3fs max=%.3fs -> %s\n",
            $label,
            $count,
            $report['min'],
            $report['p50'],
            $report['p95'],
            $report['max'],
            $path,
        ));
    }
}
