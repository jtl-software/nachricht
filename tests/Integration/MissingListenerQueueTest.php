<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\UnlistenedMessage;
use JTL\Nachricht\Serializer\PhpMessageSerializer;
use JTL\Nachricht\Transport\Amqp\AmqpTransport;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * EA-8268: a message nobody listens for must be parked on missing_listener__<routingKey>, not
 * dropped and not retried. Uncovered until now, and the parking queue is what makes a
 * misconfigured deployment recoverable instead of lossy.
 */
#[TestDox('Messages without a listener: the missing-listener queue')]
final class MissingListenerQueueTest extends IntegrationTestCase
{
    #[TestDox('a message with no registered listener is parked on the missing-listener queue')]
    public function testMessageWithoutListenerIsParked(): void
    {
        $routingKey = UnlistenedMessage::getRoutingKey();
        $this->purgeQueuesFor($routingKey);

        // UnlistenedMessage is deliberately absent from the MessageCache, so
        // ListenerProvider::eventHasListeners() reports false for it.
        $transport = $this->createTransport([]);

        $this->subscribe(
            $transport,
            $this->subscriptionFor($routingKey),
            static function (): void {
                self::fail('handler must not be invoked for a message without a listener');
            },
        );

        $transport->publish(new UnlistenedMessage(payload: 'nobody-listens'));

        $this->pollFor($transport, 5.0);

        $parked = $transport->getMessageFromQueue(
            AmqpTransport::MISSING_LISTENER_QUEUE_PREFIX . $routingKey,
            true,
        );
        self::assertNotNull($parked, 'message was not parked on the missing-listener queue');

        $restored = (new PhpMessageSerializer())->deserialize($parked->getBody());
        self::assertInstanceOf(UnlistenedMessage::class, $restored);
        self::assertSame('nobody-listens', $restored->getPayload());
    }
}
