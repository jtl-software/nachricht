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
    private string $host;
    private string $port;
    private string $user;
    private string $password;
    private string $vhost;
    private string $httpPort;

    public function __construct(
        string $host,
        string $port,
        string $httpPort,
        string $user,
        string $password,
        string $vhost = '/'
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->password = $password;
        $this->vhost = $vhost;
        $this->httpPort = $httpPort;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): string
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
}
