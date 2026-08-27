<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\ConsumerLoopTestMessage;
use JTL\Nachricht\Integration\Fixtures\RecordingListener;
use JTL\Nachricht\Transport\Amqp\AmqpTransport;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * EA-8268: drives the whole production consume path - AmqpConsumer's poll loop, its
 * renewSubscription-on-idle-timeout behaviour, its ttl shutdown, and dispatch through
 * AmqpDispatcher and ListenerProvider into a listener resolved from the container. The
 * transport-level pollFor() helper the other tests use touches none of that.
 */
#[TestDox('AmqpConsumer: the consume loop used in production')]
final class ConsumerLoopTest extends IntegrationTestCase
{
    #[TestDox('dispatches to the listener resolved from the container and shuts down on its ttl')]
    public function testConsumerDispatchesToListenerAndHonoursTtl(): void
    {
        $routingKey = ConsumerLoopTestMessage::getRoutingKey();
        $this->purgeQueuesFor($routingKey);

        $listener = new RecordingListener();
        $listenerProvider = $this->createListenerProvider(
            [ConsumerLoopTestMessage::class],
            [ConsumerLoopTestMessage::class => $listener],
        );
        $transport = $this->createTransportWithProvider($listenerProvider);

        // delay=0 goes to the default exchange with the queue name as routing key, and publish()
        // declares that queue first - so unlike a delayed message this is safely queued even
        // before any consumer exists.
        $this->createEmitter($transport)->emit(new ConsumerLoopTestMessage(payload: 'via-consumer'));

        $consumer = $this->createConsumer($transport, $listenerProvider);

        $startedAt = microtime(true);
        // ttl=5 with a 1s poll timeout: the message arrives on the first poll, then the queue
        // goes idle and every further poll times out. The consumer must still shut down on its
        // ttl - it used to renew forever, because the ttl was only checked after a poll that
        // did not time out.
        $consumer->consume($this->subscriptionFor($routingKey, ttl: 5), timeout: 1);
        $elapsed = microtime(true) - $startedAt;

        self::assertSame(['via-consumer'], $listener->payloads());
        self::assertLessThan(30.0, $elapsed, 'consumer did not shut down on its ttl while the queue was idle');

        $this->assertQueueCountersEventually(
            AmqpTransport::MESSAGE_QUEUE_PREFIX . $routingKey,
            0,
            0,
            'the consumer left messages ready or unacknowledged after shutting down',
        );
    }
}
