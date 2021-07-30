<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Transport\Amqp;

use PHPUnit\Framework\TestCase;

/**
 * Class AmqpConnectionSettingsTest
 * @package JTL\Nachricht\Transport\Amqp
 *
 * @covers \JTL\Nachricht\Transport\Amqp\AmqpConnectionSettings
 */
class AmqpConnectionSettingsTest extends TestCase
{
    public function testCanBeCreated(): void
    {
        $host = uniqid('host', true);
        $port = uniqid('port', true);
        $httpPort = uniqid('httpPort', true);
        $user = uniqid('user', true);
        $password = uniqid('password', true);
        $vhost = uniqid('vhost', true);

        $connectionSettings = new AmqpConnectionSettings(
            $host,
            $port,
            $httpPort,
            $user,
            $password,
            $vhost
        );

        $this->assertEquals($host, $connectionSettings->getHost());
        $this->assertEquals($port, $connectionSettings->getPort());
        $this->assertEquals($httpPort, $connectionSettings->getHttpPort());
        $this->assertEquals($user, $connectionSettings->getUser());
        $this->assertEquals($password, $connectionSettings->getPassword());
        $this->assertEquals($vhost, $connectionSettings->getVhost());
    }
}
