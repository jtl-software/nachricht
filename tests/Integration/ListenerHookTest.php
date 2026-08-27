<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Nachricht\Integration\Fixtures\HookedListener;
use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\IntegrationTestMessage;
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

        // retryDelay=1 so the re-queued message comes back well inside the ttl window.
        $this->runThroughConsumer($listener, ttl: 8, retryDelay: 1);

        $calls = $listener->calls();
        self::assertSame(['setup', 'handle', 'onError', 'after'], array_slice($calls, 0, 4));
        self::assertContains('handle', array_slice($calls, 4), 'message was not re-queued after onError re-threw');
        self::assertGreaterThanOrEqual(
            2,
            count(array_filter($calls, static fn (string $call): bool => $call === 'handle')),
            'listener should have been invoked again for the retry',
        );
    }

    private function runThroughConsumer(HookedListener $listener, int $ttl, int $retryDelay = 1): void
    {
        $routingKey = IntegrationTestMessage::getRoutingKey();
        $this->purgeQueuesFor($routingKey);

        $listenerProvider = $this->createListenerProvider(
            [IntegrationTestMessage::class],
            [IntegrationTestMessage::class => $listener],
        );
        $transport = $this->createTransportWithProvider($listenerProvider);

        $this->createEmitter($transport)->emit(
            new IntegrationTestMessage(payload: 'hooked', retryDelay: $retryDelay),
        );

        $this->createConsumer($transport, $listenerProvider)
            ->consume($this->subscriptionFor($routingKey, ttl: $ttl), timeout: 1);
    }
}
