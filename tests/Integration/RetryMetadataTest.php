<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\IntegrationTestMessage;
use JTL\Nachricht\Serializer\PhpMessageSerializer;
use JTL\Nachricht\Transport\Amqp\AmqpTransport;
use PHPUnit\Framework\Attributes\TestDox;
use RuntimeException;

/**
 * EA-8268: the relation matrix proves a failing message eventually dead-letters, but not that
 * the metadata carrying that decision survives the round trip through the broker. receiveCount
 * is incremented by __wakeup() on deserialization and compared against the retry count - if
 * that ever stopped working, messages would retry forever instead of dead-lettering, which is
 * exactly the kind of silent infinite loop that fills a queue.
 */
#[TestDox('Retry metadata carried across redeliveries')]
final class RetryMetadataTest extends IntegrationTestCase
{
    #[TestDox('the dead-lettered message keeps its id and carries the last error and retry count')]
    public function testDeadLetteredMessageCarriesRetryMetadata(): void
    {
        $routingKey = IntegrationTestMessage::getRoutingKey();
        $this->purgeQueuesFor($routingKey);

        $transport = $this->createTransport([IntegrationTestMessage::class]);
        $message = new IntegrationTestMessage(payload: 'always-fails', retryDelay: 1);
        $originalMessageId = $message->getMessageId();

        $seenMessageIds = [];
        $this->subscribe(
            $transport,
            $this->subscriptionFor($routingKey),
            function (IntegrationTestMessage $received) use (&$seenMessageIds): void {
                $seenMessageIds[] = $received->getMessageId();
                throw new RuntimeException('integration test: permanent failure');
            },
        );

        $transport->publish($message);

        // DEFAULT_RETRY_COUNT is 3, retryDelay 1s: three deliveries plus the dead-letter hop.
        $this->pollFor($transport, 12.0);

        self::assertNotEmpty($seenMessageIds);
        self::assertSame(
            [$originalMessageId],
            array_unique($seenMessageIds),
            'the message id changed across redeliveries',
        );

        $deadLettered = $transport->getMessageFromQueue(
            AmqpTransport::DEAD_LETTER_QUEUE_PREFIX . $routingKey,
            true,
        );
        self::assertNotNull($deadLettered, 'message never reached the dead-letter queue');

        $restored = (new PhpMessageSerializer())->deserialize($deadLettered->getBody());
        self::assertInstanceOf(IntegrationTestMessage::class, $restored);
        self::assertSame($originalMessageId, $restored->getMessageId());
        self::assertSame('always-fails', $restored->getPayload());
        self::assertStringContainsString(
            'permanent failure',
            (string)$restored->getLastErrorMessage(),
            'the failure reason was not recorded on the dead-lettered message',
        );
        // >= rather than == : this read deserialized the message once more, which bumps the
        // counter again via __wakeup().
        self::assertGreaterThanOrEqual(
            $restored->getRetryCount(),
            $restored->getReceiveCount(),
            'receiveCount did not reach the retry count, so dead-lettering was not driven by it',
        );
    }
}
