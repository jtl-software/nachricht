<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\IntegrationTestMessage;

/**
 * EA-8268 / EA-7964: publishing a long delay before a short one must not affect delivery order.
 * This is the exact failure mode that killed the earlier waiting-room approach - worth its own
 * dedicated test rather than folding it into the relation matrix.
 */
final class MixedOrderDeliveryTest extends IntegrationTestCase
{
    public function testShortDelayIsDeliveredBeforeLongerDelayPublishedFirst(): void
    {
        $routingKey = IntegrationTestMessage::getRoutingKey();
        $this->purgeQueuesFor($routingKey);

        $transport = $this->createTransport([IntegrationTestMessage::class]);

        /** @var array<int, string> $deliveryOrder */
        $deliveryOrder = [];
        $handler = function (IntegrationTestMessage $received) use (&$deliveryOrder): void {
            $deliveryOrder[] = $received->getPayload();
        };

        $this->subscribe($transport, $this->subscriptionFor($routingKey), $handler);

        // Publish out of delay order: the 10s message first, the 2s message second.
        $transport->publish(new IntegrationTestMessage(payload: 'long', delay: 10), 10);
        $transport->publish(new IntegrationTestMessage(payload: 'short', delay: 2), 2);

        $this->pollFor($transport, 13.0);

        self::assertSame(['short', 'long'], $deliveryOrder, 'delivery order did not follow delay order');
    }
}
