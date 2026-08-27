<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration\Fixtures;

use JTL\Nachricht\Message\AbstractAmqpTransportableMessage;

/**
 * Carries a payload wide enough to catch serialization or transport corruption that a short
 * ASCII string would not: a large body, unicode, null bytes, and a nested structure with mixed
 * scalar types.
 */
final class ComplexPayloadMessage extends AbstractAmqpTransportableMessage
{
    /**
     * @param array<string, mixed> $structure
     */
    public function __construct(
        private readonly string $blob,
        private readonly array $structure,
        int $delay = self::ENQUEUE_DELAY,
    ) {
        parent::__construct(delay: $delay);
    }

    public function getBlob(): string
    {
        return $this->blob;
    }

    /**
     * @return array<string, mixed>
     */
    public function getStructure(): array
    {
        return $this->structure;
    }
}
