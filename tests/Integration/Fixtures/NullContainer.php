<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration\Fixtures;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

/**
 * ListenerProvider requires a ContainerInterface, but AmqpTransport never resolves listeners
 * through it directly - it only calls ListenerProvider::eventHasListeners(), which reads the
 * MessageCache. This stub exists purely to satisfy the constructor; it is never expected to be
 * called by the tests in this suite.
 */
final class NullContainer implements ContainerInterface
{
    public function get(string $id): never
    {
        throw new class("Unexpected container lookup for \"{$id}\" in integration test stub") extends RuntimeException implements NotFoundExceptionInterface {
        };
    }

    public function has(string $id): bool
    {
        return false;
    }
}
