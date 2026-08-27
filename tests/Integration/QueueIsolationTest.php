<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Generic\StringCollection;
use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\IntegrationTestMessage;
use JTL\Nachricht\Integration\Fixtures\IntegrationTestMessageAlt;
use JTL\Nachricht\Transport\Amqp\AmqpTransport;
use JTL\Nachricht\Transport\SubscriptionSettings;

/**
 * EA-8268: proves two independent routing keys bound to the same delayed_exchange
 * (x-delayed-type: direct) never cross-deliver, under the new plugin fork.
 */
final class QueueIsolationTest extends IntegrationTestCase
{
    public function testTwoRoutingKeysDoNotCrossDeliver(): void
    {
        $routingKeyA = IntegrationTestMessage::getRoutingKey();
        $routingKeyB = IntegrationTestMessageAlt::getRoutingKey();
        $this->purgeQueuesFor($routingKeyA);
        $this->purgeQueuesFor($routingKeyB);

        $transport = $this->createTransport([IntegrationTestMessage::class, IntegrationTestMessageAlt::class]);

        /** @var array<int, string> $receivedOnA */
        $receivedOnA = [];
        /** @var array<int, string> $receivedOnB */
        $receivedOnB = [];

        $subscriptionForBoth = new SubscriptionSettings(StringCollection::from(
            AmqpTransport::MESSAGE_QUEUE_PREFIX . $routingKeyA,
            AmqpTransport::MESSAGE_QUEUE_PREFIX . $routingKeyB,
        ));

        $handler = function (IntegrationTestMessage $received) use (&$receivedOnA, &$receivedOnB): void {
            if ($received instanceof IntegrationTestMessageAlt) {
                $receivedOnB[] = $received->getPayload();
            } else {
                $receivedOnA[] = $received->getPayload();
            }
        };

        $this->subscribe($transport, $subscriptionForBoth, $handler);

        $transport->publish(new IntegrationTestMessage(payload: 'for-a', delay: 2), 2);
        $transport->publish(new IntegrationTestMessageAlt(payload: 'for-b', delay: 2), 2);

        $this->pollFor($transport, 5.0);

        self::assertSame(['for-a'], $receivedOnA, 'routing key A received a message not meant for it (or missed its own)');
        self::assertSame(['for-b'], $receivedOnB, 'routing key B received a message not meant for it (or missed its own)');
    }
}
