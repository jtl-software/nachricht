<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 21/05/19
 */

namespace JTL\Nachricht\Message;

use JTL\Nachricht\Contract\Message\AmqpTransportableMessage;
use Ramsey\Uuid\Uuid;

/**
 * AbstractAmqpMessage represents the base class for AmqpMessage.
 *
 * It is recommend to extend these abstraction when working with the
 * AMQP Transport for your Messages.
 *
 * The class will provide some basic functionality such as:
 *
 *  - detection when a message should be dead lettered (receive maximum is reached)
 *  - increment receiveCount after unserialize() (using magic __wakeup())
 *  - store the last error message which can be used for debugging to see why a event
 *    was getting re-queued or dead lettered
 *  - creating a default eventId (based on Ramsey\Uuid) which can be used for logging
 *    in your application
 *  - define the routing key (or name of the message queue) - which is static:class
 *
 * If you do not want to rely on these abstraction. All you need is to implement the
 * AmqpMessage interface for your Message implementation.
 */
abstract class AbstractAmqpTransportableMessage implements AmqpTransportableMessage
{
    public const DEFAULT_RETRY_COUNT = 3;

    private int $__receiveCount = 0;

    private ?string $__lastErrorMessage;

    private string $__eventId;

    private \DateTimeImmutable $createdAt;

    public const RETRY_DELAY = 1;
    public const ENQUEUE_DELAY = 0;

    private string $exchange = 'direct_exchange';

    public function __construct(
        string $messageId = null,
        \DateTimeImmutable $createdAt = null,
        private int $delay = self::ENQUEUE_DELAY,
        private int $retryDelay = self::RETRY_DELAY
    ) {
        $this->__eventId = $messageId ?? Uuid::uuid4()->toString();
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public static function getRoutingKey(): string
    {
        return self::getDefaultRoutingKey();
    }

    public function getExchange(): string
    {
        return $this->exchange;
    }

    public function setExchange(string $exchange): void
    {
        $this->exchange = $exchange;
    }

    private static function getDefaultRoutingKey(): string
    {
        return static::class;
    }

    public function getMessageId(): string
    {
        return $this->__eventId;
    }

    public function setLastError(string $errorMessage): void
    {
        $this->__lastErrorMessage = $errorMessage;
    }

    public function getLastErrorMessage(): ?string
    {
        return $this->__lastErrorMessage;
    }

    public function getReceiveCount(): int
    {
        return $this->__receiveCount;
    }

    public function setReceiveCount(int $receiveCount): void
    {
        $this->__receiveCount = $receiveCount;
    }

    public function isDeadLetter(): bool
    {
        return $this->__receiveCount >= $this->getRetryCount();
    }

    public function getRetryCount(): int
    {
        return static::DEFAULT_RETRY_COUNT;
    }

    public function __wakeup()
    {
        ++$this->__receiveCount;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }
}
