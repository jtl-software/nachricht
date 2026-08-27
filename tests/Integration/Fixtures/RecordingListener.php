<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration\Fixtures;

use JTL\Nachricht\Contract\Message\Message;
use RuntimeException;

/**
 * Listener used by the tests that drive the real production consume path. Records every
 * invocation (payload + timestamp) and can be told to throw for the first N calls, so the
 * retry / dead-letter behaviour can be driven deterministically through ListenerProvider and
 * AmqpDispatcher rather than through a hand-rolled closure.
 */
class RecordingListener
{
    /**
     * @var array<int, array{payload: string, at: float}>
     */
    private array $invocations = [];

    public function __construct(private int $failuresBeforeSuccess = 0)
    {
    }

    public function handle(Message $message): void
    {
        /** @var IntegrationTestMessage $message */
        $this->invocations[] = ['payload' => $message->getPayload(), 'at' => microtime(true)];

        if (count($this->invocations) <= $this->failuresBeforeSuccess) {
            throw new RuntimeException('integration test listener: deliberate failure');
        }
    }

    /**
     * @return array<int, array{payload: string, at: float}>
     */
    public function invocations(): array
    {
        return $this->invocations;
    }

    /**
     * @return array<int, string>
     */
    public function payloads(): array
    {
        return array_column($this->invocations, 'payload');
    }

    public function invocationCount(): int
    {
        return count($this->invocations);
    }
}
