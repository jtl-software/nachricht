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
     * $timeout used to feed the connection timeout, the socket read/write timeout and the
     * channel RPC timeout all at once. They are separate now because a heartbeat forces the
     * read/write timeout to be at least twice the heartbeat interval, while the other two want
     * to stay short. Leaving the new parameters at null keeps the previous behaviour exactly:
     * every timeout equals $timeout and no heartbeat is negotiated.
     *
     * @param int $heartbeat Heartbeat interval in seconds, 0 disables it. With a heartbeat the
     *                       client detects a broker that stopped answering (see AmqpConsumer,
     *                       which then no longer needs to tear down its subscription to notice).
     */
    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $httpPort,
        private readonly string $user,
        private readonly string $password,
        private readonly string $vhost = '/',
        private readonly float $timeout = 3.0,
        private readonly int $heartbeat = 0,
        private readonly ?float $readWriteTimeout = null,
        private readonly ?float $channelRpcTimeout = null,
    ) {
        if ($this->heartbeat < 0) {
            throw new InvalidArgumentException('heartbeat must not be negative');
        }

        if ($this->heartbeat > 0 && $this->getReadWriteTimeout() < $this->heartbeat * 2) {
            throw new InvalidArgumentException(sprintf(
                'readWriteTimeout (%.1fs) must be at least twice the heartbeat (%ds)',
                $this->getReadWriteTimeout(),
                $this->heartbeat,
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
        return $this->heartbeat;
    }

    /**
     * Socket read/write timeout. Defaults to $timeout, widened to twice the heartbeat when one
     * is configured, because php-amqplib rejects anything smaller.
     */
    public function getReadWriteTimeout(): float
    {
        return $this->readWriteTimeout ?? max($this->timeout, $this->heartbeat * 2.0);
    }

    public function getChannelRpcTimeout(): float
    {
        return $this->channelRpcTimeout ?? $this->timeout;
    }
}
