<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/17
 */

namespace JTL\Nachricht\Transport\Amqp;

use InvalidArgumentException;

class AmqpConnectionSettings
{
    /**
     * Matches RabbitMQ's own server-side default, so the negotiated interval is the one the
     * broker expects anyway.
     */
    public const DEFAULT_HEARTBEAT = 60;

    /**
     * $timeout used to feed the connection timeout, the socket read/write timeout and the
     * channel RPC timeout all at once. They are separate now because a heartbeat forces the
     * read/write timeout to be at least twice the heartbeat interval, while the other two want
     * to stay short.
     *
     * @param int|null $heartbeat Heartbeat interval in seconds. null (the default) picks
     *                            DEFAULT_HEARTBEAT, because a connection without a heartbeat
     *                            cannot tell a silent broker from a quiet one - and detecting
     *                            that is what lets AmqpConsumer stop tearing down its
     *                            subscription on every idle poll, which is where deliveries
     *                            were being lost. Pass 0 to switch it off explicitly and get
     *                            the previous renew-on-idle behaviour back.
     * @param float|null $readWriteTimeout Socket read/write timeout. null derives a value that
     *                            satisfies the heartbeat; php-amqplib rejects anything below
     *                            twice the interval.
     */
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $httpPort,
        private readonly string $user,
        private readonly string $password,
        private readonly string $vhost = '/',
        private readonly float $timeout = 3.0,
        private readonly ?int $heartbeat = null,
        private readonly ?float $readWriteTimeout = null,
        private readonly ?float $channelRpcTimeout = null,
    ) {
        if ($this->heartbeat !== null && $this->heartbeat < 0) {
            throw new InvalidArgumentException('heartbeat must not be negative');
        }

        $heartbeat = $this->getHeartbeat();
        if ($heartbeat > 0 && $this->getReadWriteTimeout() < $heartbeat * 2) {
            throw new InvalidArgumentException(sprintf(
                'readWriteTimeout (%.1fs) must be at least twice the heartbeat (%ds)',
                $this->getReadWriteTimeout(),
                $heartbeat,
            ));
        }
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getVhost(): string
    {
        return $this->vhost;
    }

    public function getHttpPort(): string
    {
        return $this->httpPort;
    }

    /**
     * Connection timeout in seconds.
     */
    public function getTimeout(): float
    {
        return $this->timeout;
    }

    public function getHeartbeat(): int
    {
        return $this->heartbeat ?? self::DEFAULT_HEARTBEAT;
    }

    /**
     * Socket read/write timeout. Defaults to $timeout, widened to twice the heartbeat when one
     * is in effect, because php-amqplib rejects anything smaller.
     */
    public function getReadWriteTimeout(): float
    {
        return $this->readWriteTimeout ?? max($this->timeout, $this->getHeartbeat() * 2.0);
    }

    public function getChannelRpcTimeout(): float
    {
        return $this->channelRpcTimeout ?? $this->timeout;
    }
}
