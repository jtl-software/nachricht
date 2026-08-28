<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Nachricht\Integration\Fixtures\IdleSubscriptionTestMessage;
use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\RecordingListener;
use JTL\Nachricht\Transport\Amqp\AmqpTransport;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Regression test for the delivery lost during renewSubscription().
 *
 * Before the heartbeat change, AmqpConsumer renewed its subscription on every poll timeout. A
 * message that became deliverable inside that cancel/consume window never reached the listener:
 * the broker counted it as delivered, so it sat UNACKED forever - not handled, not retried, not
 * dead-lettered. With a heartbeat configured the consumer leaves its subscription alone, and
 * the message arrives.
 *
 * The timing here is deliberately hostile: a 1s poll timeout against a 1s delay means an idle
 * timeout falls due at almost exactly the moment the message does. Production polls every 20s,
 * so it would hit this rarely rather than reliably - the point of the test is to make a rare
 * race deterministic, not to mirror production timing.
 */
#[TestDox('Idle handling: a message due while the consumer idles must not be lost')]
final class IdleSubscriptionTest extends IntegrationTestCase
{
    #[TestDox('a delayed message due during an idle poll still reaches the listener')]
    public function testDelayedMessageDueWhileIdleIsStillDelivered(): void
    {
        $routingKey = IdleSubscriptionTestMessage::getRoutingKey();
        $this->purgeQueuesFor($routingKey);

        $listener = new RecordingListener();
        $listenerProvider = $this->createListenerProvider(
            [IdleSubscriptionTestMessage::class],
            [IdleSubscriptionTestMessage::class => $listener],
        );
        $transport = $this->createTransportWithProvider($listenerProvider);

        if ($transport->getConnectionSettings()->getHeartbeat() === 0) {
            // Without a heartbeat the consumer still renews on idle by design, and this delivery
            // is expected to be lost - that is the behaviour the heartbeat replaces, not a
            // regression to assert against.
            self::markTestSkipped('heartbeat disabled - the legacy renew-on-idle path is in use');
        }

        // Bind before publishing - the delayed exchange drops what it cannot route once due.
        $consumer = $this->createConsumer($transport, $listenerProvider);
        $transport->publish(new IdleSubscriptionTestMessage(payload: 'due-while-idle', delay: 1), 1);

        $consumer->consume($this->subscriptionFor($routingKey, ttl: 12), timeout: 1);

        self::assertSame(['due-while-idle'], $listener->payloads(), 'the message was lost while the consumer idled');
        $this->assertQueueCountersEventually(
            AmqpTransport::MESSAGE_QUEUE_PREFIX . $routingKey,
            0,
            0,
            'the message was delivered but never acknowledged',
        );
    }
}
