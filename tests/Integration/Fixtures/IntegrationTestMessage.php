<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration\Fixtures;

use JTL\Nachricht\Message\AbstractAmqpTransportableMessage;

/**
 * Concrete AmqpTransportableMessage used by the RabbitMQ testbed integration tests.
 *
 * getRoutingKey() is static and derived from the class name (see
 * AbstractAmqpTransportableMessage), so every instance of this class shares one queue.
 * Tests that need message isolation on a separate queue use IntegrationTestMessageAlt instead
 * of instantiating this class with different arguments.
 */
class IntegrationTestMessage extends AbstractAmqpTransportableMessage
{
    public function __construct(
        private readonly string $payload,
        int $delay = self::ENQUEUE_DELAY,
        int $retryDelay = self::RETRY_DELAY,
    ) {
        parent::__construct(delay: $delay, retryDelay: $retryDelay);
    }

    public function getPayload(): string
    {
        return $this->payload;
    }
}
