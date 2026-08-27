<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration\Fixtures;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

/**
 * Minimal PSR-11 container over a fixed map, used by the tests that drive the real production
 * consume path (AmqpConsumer -> AmqpDispatcher -> ListenerProvider -> container->get()).
 * NullContainer cannot serve those, because ListenerProvider really does resolve the listener
 * instance through the container there.
 */
final class ArrayContainer implements ContainerInterface
{
    /**
     * @param array<string, object> $services
     */
    public function __construct(private readonly array $services)
    {
    }

    public function get(string $id): object
    {
        if (!isset($this->services[$id])) {
            throw new class("Service \"{$id}\" is not registered in the integration test container") extends RuntimeException implements NotFoundExceptionInterface {
            };
        }

        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}
