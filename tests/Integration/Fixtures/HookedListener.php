<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration\Fixtures;

use JTL\Nachricht\Contract\Hook\AfterMessageErrorHook;
use JTL\Nachricht\Contract\Hook\AfterMessageHook;
use JTL\Nachricht\Contract\Hook\BeforeMessageHook;
use JTL\Nachricht\Contract\Message\Message;
use RuntimeException;
use Throwable;

/**
 * Listener implementing all three lifecycle hooks, recording the exact call order so the tests
 * can assert it (setup -> handle -> [onError] -> after).
 *
 * $swallowErrors mirrors the documented AfterMessageErrorHook contract: re-throwing from
 * onError() lets Nachricht re-queue the message, not re-throwing marks processing as
 * successful. Both branches are worth covering - they decide whether a failure retries at all.
 */
final class HookedListener implements BeforeMessageHook, AfterMessageHook, AfterMessageErrorHook
{
    /**
     * @var array<int, string>
     */
    private array $calls = [];

    public function __construct(
        private readonly bool $throwInHandler = false,
        private readonly bool $swallowErrors = true,
    ) {
    }

    public function setup(Message $message): void
    {
        $this->calls[] = 'setup';
    }

    public function handle(Message $message): void
    {
        $this->calls[] = 'handle';

        if ($this->throwInHandler) {
            throw new RuntimeException('integration test hooked listener: deliberate failure');
        }
    }

    public function onError(Message $message, Throwable $throwable): void
    {
        $this->calls[] = 'onError';

        if (!$this->swallowErrors) {
            throw $throwable;
        }
    }

    public function after(Message $message): void
    {
        $this->calls[] = 'after';
    }

    /**
     * @return array<int, string>
     */
    public function calls(): array
    {
        return $this->calls;
    }
}
