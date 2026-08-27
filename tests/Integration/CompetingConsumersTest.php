<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\IntegrationTestMessage;
use JTL\Nachricht\Transport\Amqp\AmqpTransport;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * EA-8268: production runs several consumer processes against the same queue, so exactly-once
 * delivery across competing consumers is a property worth pinning down - a prefetch or ack
 * regression would show up here as a duplicate or a lost message.
 *
 * Only the exactly-once invariant is asserted, not the distribution between the two consumers:
 * with prefetch=1 and a single-threaded test polling one connection after the other, how the
 * broker splits the batch is timing-dependent and would make the test flaky for no benefit.
 */
#[TestDox('Competing consumers on one queue')]
final class CompetingConsumersTest extends IntegrationTestCase
{
    private const MESSAGE_COUNT = 6;

    #[TestDox('every message is delivered exactly once across two consumers')]
    public function testEachMessageIsDeliveredExactlyOnce(): void
    {
        $routingKey = IntegrationTestMessage::getRoutingKey();
        $queueName = AmqpTransport::MESSAGE_QUEUE_PREFIX . $routingKey;
        $this->purgeQueuesFor($routingKey);

        $listenerProvider = $this->createListenerProvider([IntegrationTestMessage::class]);
        $first = $this->createTransportWithProvider($listenerProvider);
        // A second, independent connection - createTransportWithProvider only tracks the last
        // instance for teardown, so this one is closed explicitly at the end of the test.
        $second = $this->createTransportWithProvider($listenerProvider);

        /** @var array<int, string> $receivedByFirst */
        $receivedByFirst = [];
        /** @var array<int, string> $receivedBySecond */
        $receivedBySecond = [];

        $this->subscribe(
            $first,
            $this->subscriptionFor($routingKey),
            function (IntegrationTestMessage $message) use (&$receivedByFirst): void {
                $receivedByFirst[] = $message->getPayload();
            },
        );
        $this->subscribe(
            $second,
            $this->subscriptionFor($routingKey),
            function (IntegrationTestMessage $message) use (&$receivedBySecond): void {
                $receivedBySecond[] = $message->getPayload();
            },
        );

        for ($i = 0; $i < self::MESSAGE_COUNT; ++$i) {
            $first->publish(new IntegrationTestMessage(payload: "message-{$i}"));
        }

        $this->pollBothFor($first, $second, 8.0);

        $all = array_merge($receivedByFirst, $receivedBySecond);
        sort($all);

        $expected = array_map(static fn (int $i): string => "message-{$i}", range(0, self::MESSAGE_COUNT - 1));
        sort($expected);

        self::assertSame($expected, $all, 'messages were lost or delivered more than once');

        $counters = $this->managementClient()->queueCounters($queueName);
        self::assertNotNull($counters);
        self::assertSame(0, $counters['ready']);
        self::assertSame(0, $counters['unacknowledged'], 'a message was left unacknowledged');

        unset($second);
        gc_collect_cycles();
    }

    private function pollBothFor(AmqpTransport $first, AmqpTransport $second, float $timeoutSeconds): void
    {
        $deadline = microtime(true) + $timeoutSeconds;

        while (microtime(true) < $deadline) {
            foreach ([$first, $second] as $transport) {
                try {
                    $transport->poll(1);
                } catch (AMQPTimeoutException) {
                    // Nothing for this consumer in this window - try the other one.
                }
            }
        }
    }
}
