<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/17
 */

namespace JTL\Nachricht\Transport\Amqp;

class AmqpConnectionSettings
{

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $httpPort,
        private readonly string $user,
        private readonly string $password,
        private readonly string $vhost = '/',
        private readonly float $timeout = 3.0
    ) {
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

    public function getTimeout(): float
    {
        return $this->timeout;
    }
}
