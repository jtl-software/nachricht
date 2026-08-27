<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Nachricht\Integration\Fixtures\HookedListener;
use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Transport\Amqp\AmqpTransport;
use JTL\Nachricht\Integration\Fixtures\HookTestMessage;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * EA-8268: the listener lifecycle hooks had no coverage at all, and they decide whether a
 * failure retries. Per the AfterMessageErrorHook contract, re-throwing from onError() lets
 * Nachricht re-queue the message while swallowing it marks processing as successful - both
 * branches run through the real consumer, dispatcher and ListenerProvider here.
 */
#[TestDox('Listener lifecycle hooks (setup / after / onError)')]
final class ListenerHookTest extends IntegrationTestCase
{
    #[TestDox('calls setup then the listener then after on success')]
    public function testHookOrderOnSuccess(): void
    {
        $listener = new HookedListener();

        $this->runThroughConsumer($listener, ttl: 4);

        self::assertSame(['setup', 'handle', 'after'], $listener->calls());
    }

    #[TestDox('calls onError before after when the listener throws, and swallowing it prevents a retry')]
    public function testSwallowedErrorIsNotRetried(): void
    {
        $listener = new HookedListener(throwInHandler: true, swallowErrors: true);

        $this->runThroughConsumer($listener, ttl: 6);

        self::assertSame(['setup', 'handle', 'onError', 'after'], $listener->calls());
    }

    #[TestDox('re-throwing from onError re-queues the message, so the listener runs again')]
    public function testRethrownErrorCausesARetry(): void
    {
        $listener = new HookedListener(throwInHandler: true, swallowErrors: false);

        // retryDelay=1 against a 20s ttl: deliberately generous. The retry travels through the
        // delayed exchange while the consume loop keeps renewing its subscription on every idle
        // timeout, and a tighter window turned out to be flaky on the older plugin. If a 1s
        // retry cannot come back within 20s, that is a real defect rather than a slow runner,
        // so the failure message carries where the message actually ended up.
        $routingKey = HookTestMessage::getRoutingKey();
        $this->runThroughConsumer($listener, ttl: 20, retryDelay: 1);

        $calls = $listener->calls();
        self::assertSame(['setup', 'handle', 'onError', 'after'], array_slice($calls, 0, 4));

        $handleCount = count(array_filter($calls, static fn (string $call): bool => $call === 'handle'));
        self::assertGreaterThanOrEqual(
            2,
            $handleCount,
            'the message was not re-queued after onError re-threw. ' . $this->describeQueueState($routingKey),
        );
    }

    /**
     * Turns "the retry never came back" into something actionable: whether the message is still
     * waiting in the queue, sitting unacknowledged, already dead-lettered, or gone entirely.
     */
    private function describeQueueState(string $routingKey): string
    {
        $client = $this->managementClient();
        $main = $client->queueCounters(AmqpTransport::MESSAGE_QUEUE_PREFIX . $routingKey);
        $deadLetter = $client->queueCounters(AmqpTransport::DEAD_LETTER_QUEUE_PREFIX . $routingKey);

        return sprintf(
            'Queue state - main: %s, dead-letter: %s',
            $main === null ? 'absent' : sprintf('ready=%d unacked=%d', $main['ready'], $main['unacknowledged']),
            $deadLetter === null ? 'absent' : sprintf('ready=%d unacked=%d', $deadLetter['ready'], $deadLetter['unacknowledged']),
        );
    }

    private function runThroughConsumer(HookedListener $listener, int $ttl, int $retryDelay = 1): void
    {
        $routingKey = HookTestMessage::getRoutingKey();
        $this->purgeQueuesFor($routingKey);

        $listenerProvider = $this->createListenerProvider(
            [HookTestMessage::class],
            [HookTestMessage::class => $listener],
        );
        $transport = $this->createTransportWithProvider($listenerProvider);

        $this->createEmitter($transport)->emit(
            new HookTestMessage(payload: 'hooked', retryDelay: $retryDelay),
        );

        $this->createConsumer($transport, $listenerProvider)
            ->consume($this->subscriptionFor($routingKey, ttl: $ttl), timeout: 1);
    }
}
