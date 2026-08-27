<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\RelationMatrixTestMessage;
use JTL\Nachricht\Transport\Amqp\AmqpTransport;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use RuntimeException;

/**
 * EA-8268: exercises every combination of delay x outcome against a real broker, so a
 * regression in the delayed-message path (initial scheduling, app-managed retry, dead-letter)
 * shows up here instead of only during a manual STAGE validation pass.
 */
#[TestDox('Delayed-message delivery: every delay x outcome combination')]
final class RelationMatrixTest extends IntegrationTestCase
{
    /**
     * @return iterable<string, array{int, string}>
     */
    public static function relationMatrix(): iterable
    {
        foreach ([0, 2, 10] as $delay) {
            foreach (['success', 'retry-then-succeed', 'dead-letter'] as $outcome) {
                yield "delay={$delay}s outcome={$outcome}" => [$delay, $outcome];
            }
        }
    }

    #[DataProvider('relationMatrix')]
    #[TestDox('delivers after a $delaySeconds s delay with outcome "$outcome"')]
    public function testDelayAndOutcomeCombination(int $delaySeconds, string $outcome): void
    {
        $routingKey = RelationMatrixTestMessage::getRoutingKey();
        $this->purgeQueuesFor($routingKey);

        $transport = $this->createTransport([RelationMatrixTestMessage::class]);
        $message = new RelationMatrixTestMessage(
            payload: "delay={$delaySeconds};outcome={$outcome}",
            delay: $delaySeconds,
            retryDelay: 1,
        );

        /** @var array<int, array{at: float, payload: string}> $attempts */
        $attempts = [];
        $handler = function (RelationMatrixTestMessage $received) use (&$attempts, $outcome): void {
            $attempts[] = ['at' => microtime(true), 'payload' => $received->getPayload()];

            if ($outcome === 'dead-letter') {
                throw new RuntimeException('integration test: always fails');
            }
            if ($outcome === 'retry-then-succeed' && count($attempts) === 1) {
                throw new RuntimeException('integration test: fails once');
            }
        };

        // Bind the queue BEFORE publishing - a message routed to the delayed exchange with no
        // bound queue yet is silently dropped once its delay elapses.
        $this->subscribe($transport, $this->subscriptionFor($routingKey), $handler);

        $publishedAt = microtime(true);
        $transport->publish($message, $delaySeconds);

        $timeout = $delaySeconds + match ($outcome) {
            'success' => 5.0,
            'retry-then-succeed' => 8.0,
            // DEFAULT_RETRY_COUNT is 3: two retries (1s apart) before the third failure dead-letters.
            'dead-letter' => 12.0,
        };
        $this->pollFor($transport, $timeout);

        self::assertNotEmpty($attempts, 'message was never delivered');
        self::assertSame($message->getPayload(), $attempts[0]['payload']);
        self::assertGreaterThanOrEqual(
            $delaySeconds - 0.5,
            $attempts[0]['at'] - $publishedAt,
            'message delivered before its configured delay elapsed',
        );

        match ($outcome) {
            'success' => self::assertCount(1, $attempts),
            'retry-then-succeed' => self::assertCount(2, $attempts),
            'dead-letter' => self::assertGreaterThanOrEqual(3, count($attempts)),
        };

        if ($outcome === 'dead-letter') {
            sleep(1); // give the last redelivery a moment to land on the DLQ
            $deadLetterCount = $transport->countMessagesInQueue(
                AmqpTransport::DEAD_LETTER_QUEUE_PREFIX . $routingKey,
            );
            self::assertGreaterThanOrEqual(1, $deadLetterCount, 'message never reached the dead-letter queue');
        }
    }
}
