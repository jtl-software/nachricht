<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\FailureQueueTestMessage;
use JTL\Nachricht\Transport\Amqp\AmqpTransport;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * EA-8268: a payload the serializer cannot turn back into a Message must end up on the failure
 * queue instead of being silently dropped or retried forever. This also covers directPublish()
 * and getMessageFromQueue(), the two public transport methods with no callers anywhere in the
 * workbench and no other coverage.
 *
 * Note on directPublish(): it derives the routing key from AMQPMessage::getRoutingKey(), which
 * is only populated by setDeliveryInfo() - i.e. on messages that came *from* the broker. A
 * freshly built AMQPMessage would publish to a queue literally named "msg__", so the delivery
 * info is set explicitly here, mirroring the re-publish case the method is actually built for.
 */
#[TestDox('Unprocessable payloads: the failure queue')]
final class FailureQueueTest extends IntegrationTestCase
{
    #[TestDox('a payload that cannot be deserialized is moved to the failure queue')]
    public function testUndeserializablePayloadEndsUpOnTheFailureQueue(): void
    {
        $routingKey = FailureQueueTestMessage::getRoutingKey();
        $this->purgeQueuesFor($routingKey);
        $this->purgeFailureQueue();

        $transport = $this->createTransport([FailureQueueTestMessage::class]);

        $handlerCalls = 0;
        $this->subscribe(
            $transport,
            $this->subscriptionFor($routingKey),
            function (FailureQueueTestMessage $message) use (&$handlerCalls): void {
                ++$handlerCalls;
            },
        );

        $garbage = new AMQPMessage('this is not a serialized php object');
        $garbage->setDeliveryInfo(0, false, '', $routingKey);
        $transport->directPublish($garbage);

        $this->pollFor($transport, 5.0);

        self::assertSame(0, $handlerCalls, 'an undeserializable payload must never reach the listener');

        $failed = $transport->getMessageFromQueue(AmqpTransport::FAILURE_QUEUE, true);
        self::assertNotNull($failed, 'undeserializable payload never arrived on the failure queue');
        self::assertSame('this is not a serialized php object', $failed->getBody());
    }
}
