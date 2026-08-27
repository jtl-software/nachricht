<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\EmitterTestMessage;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * EA-8268: covers the publish entry point production actually uses. scx-api injects
 * AmqpEmitter, never AmqpTransport, and emit() takes the delay from $message->getDelay()
 * instead of an explicit argument - so a break in that wiring is invisible to every test that
 * calls $transport->publish($message, $delay) directly.
 */
#[TestDox('AmqpEmitter: the publish entry point used in production')]
final class EmitterDeliveryTest extends IntegrationTestCase
{
    #[TestDox('takes the delay from the message itself and delivers a batch in delay order')]
    public function testEmitUsesTheDelayCarriedByTheMessage(): void
    {
        $routingKey = EmitterTestMessage::getRoutingKey();
        $this->purgeQueuesFor($routingKey);

        $transport = $this->createTransport([EmitterTestMessage::class]);
        $emitter = $this->createEmitter($transport);

        /** @var array<int, array{payload: string, at: float}> $received */
        $received = [];
        $this->subscribe(
            $transport,
            $this->subscriptionFor($routingKey),
            function (EmitterTestMessage $message) use (&$received): void {
                $received[] = ['payload' => $message->getPayload(), 'at' => microtime(true)];
            },
        );

        $publishedAt = microtime(true);
        // One emit() call, two messages, different delays carried on the messages.
        $emitter->emit(
            new EmitterTestMessage(payload: 'immediate', delay: 0),
            new EmitterTestMessage(payload: 'deferred', delay: 4),
        );

        $this->pollFor($transport, 9.0);

        self::assertSame(['immediate', 'deferred'], array_column($received, 'payload'));
        self::assertLessThan(
            3.0,
            $received[0]['at'] - $publishedAt,
            'the delay=0 message should have been delivered promptly',
        );
        self::assertGreaterThanOrEqual(
            3.5,
            $received[1]['at'] - $publishedAt,
            'emit() ignored the delay carried by the message',
        );
    }
}
