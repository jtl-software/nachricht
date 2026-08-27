<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\MixedOrderTestMessage;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * EA-8268 / EA-7964: publishing a long delay before a short one must not affect delivery order.
 * This is the exact failure mode that killed the earlier waiting-room approach - worth its own
 * dedicated test rather than folding it into the relation matrix.
 */
#[TestDox('Delayed-message delivery: out-of-order publish must not reorder delivery')]
final class MixedOrderDeliveryTest extends IntegrationTestCase
{
    #[TestDox('a 2s-delay message published after a 10s-delay one is still delivered first')]
    public function testShortDelayIsDeliveredBeforeLongerDelayPublishedFirst(): void
    {
        $routingKey = MixedOrderTestMessage::getRoutingKey();
        $this->purgeQueuesFor($routingKey);

        $transport = $this->createTransport([MixedOrderTestMessage::class]);

        /** @var array<int, string> $deliveryOrder */
        $deliveryOrder = [];
        $handler = function (MixedOrderTestMessage $received) use (&$deliveryOrder): void {
            $deliveryOrder[] = $received->getPayload();
        };

        $this->subscribe($transport, $this->subscriptionFor($routingKey), $handler);

        // Publish out of delay order: the 10s message first, the 2s message second.
        $transport->publish(new MixedOrderTestMessage(payload: 'long', delay: 10), 10);
        $transport->publish(new MixedOrderTestMessage(payload: 'short', delay: 2), 2);

        $this->pollFor($transport, 13.0);

        self::assertSame(['short', 'long'], $deliveryOrder, 'delivery order did not follow delay order');
    }

    #[TestDox('six interleaved delays are all delivered in delay order, not publish order')]
    public function testManyInterleavedDelaysAreDeliveredInDelayOrder(): void
    {
        $routingKey = MixedOrderTestMessage::getRoutingKey();
        $this->purgeQueuesFor($routingKey);

        $transport = $this->createTransport([MixedOrderTestMessage::class]);

        /** @var array<int, string> $deliveryOrder */
        $deliveryOrder = [];
        $handler = function (MixedOrderTestMessage $received) use (&$deliveryOrder): void {
            $deliveryOrder[] = $received->getPayload();
        };

        $this->subscribe($transport, $this->subscriptionFor($routingKey), $handler);

        // Publish order is deliberately unsorted; the plugin has to reorder all of them.
        $publishOrder = [7, 1, 5, 3, 9, 2];
        foreach ($publishOrder as $delay) {
            $transport->publish(new MixedOrderTestMessage(payload: "d{$delay}", delay: $delay), $delay);
        }

        $this->pollFor($transport, 14.0);

        $expected = $publishOrder;
        sort($expected);
        self::assertSame(
            array_map(static fn (int $delay): string => "d{$delay}", $expected),
            $deliveryOrder,
            'messages were not delivered in ascending delay order',
        );
    }
}
