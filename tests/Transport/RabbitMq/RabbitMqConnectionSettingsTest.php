<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Transport\RabbitMq;

use PHPUnit\Framework\TestCase;

/**
 * Class RabbitMqConnectionSettingsTest
 * @package JTL\Nachricht\Transport\RabbitMq
 *
 * @covers \JTL\Nachricht\Transport\RabbitMq\RabbitMqConnectionSettings
 */
class RabbitMqConnectionSettingsTest extends TestCase
{
    public function testCanBeCreated(): void
    {
        $host = uniqid('host', true);
        $port = uniqid('port', true);
        $user = uniqid('user', true);
        $password = uniqid('password', true);
        $vhost = uniqid('vhost', true);

        $connectionSettings = new RabbitMqConnectionSettings(
            $host,
            $port,
            $user,
            $password,
            $vhost
        );

        $this->assertEquals($host, $connectionSettings->getHost());
        $this->assertEquals($port, $connectionSettings->getPort());
        $this->assertEquals($user, $connectionSettings->getUser());
        $this->assertEquals($password, $connectionSettings->getPassword());
        $this->assertEquals($vhost, $connectionSettings->getVhost());
    }
}
