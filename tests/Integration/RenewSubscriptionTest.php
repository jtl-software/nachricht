<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\RenewSubscriptionTestMessage;
use JTL\Nachricht\Transport\Amqp\AmqpTransport;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * EA-8268: AmqpConsumer calls renewSubscription() on every poll timeout, which means a
 * low-traffic queue in production renews constantly - each renew is a basic_cancel followed by
 * a fresh basic_consume. If a cancel ever failed to take effect, stale consumers would pile up
 * on the queue and messages round-robined to them would sit in UNACKED forever, which is the
 * accumulation pattern seen on the Amazon channels.
 *
 * This test drives the renew cycle directly so the invariants are checked without waiting for
 * real idle timeouts: after N renewals the broker must still see exactly one consumer, and a
 * message published afterwards must be delivered exactly once and leave nothing unacknowledged.
 */
#[TestDox('AmqpTransport::renewSubscription: repeated cancel/consume cycles')]
final class RenewSubscriptionTest extends IntegrationTestCase
{
    private const RENEWALS = 5;

    #[TestDox('leaves exactly one consumer registered and still delivers exactly once')]
    public function testRepeatedRenewalsLeaveNoStaleConsumers(): void
    {
        $routingKey = RenewSubscriptionTestMessage::getRoutingKey();
        $queueName = AmqpTransport::MESSAGE_QUEUE_PREFIX . $routingKey;
        $this->purgeQueuesFor($routingKey);

        $transport = $this->createTransport([RenewSubscriptionTestMessage::class]);
        $subscription = $this->subscriptionFor($routingKey);

        /** @var array<int, string> $received */
        $received = [];
        $handler = function (RenewSubscriptionTestMessage $message) use (&$received): void {
            $received[] = $message->getPayload();
        };

        $this->subscribe($transport, $subscription, $handler);
        for ($i = 0; $i < self::RENEWALS; ++$i) {
            $transport->renewSubscription($subscription, $handler);
        }

        $this->assertConsumerCountEventually(
            $queueName,
            1,
            sprintf('after %d renewals basic_cancel did not take effect', self::RENEWALS),
        );

        $transport->publish(new RenewSubscriptionTestMessage(payload: 'after-renewals'));
        $this->pollFor($transport, 5.0);

        self::assertSame(['after-renewals'], $received, 'message was not delivered exactly once after renewals');

        $this->assertQueueCountersEventually($queueName, 0, 0, 'renewal cycle left messages behind');
    }
}
