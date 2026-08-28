<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Transport\Amqp;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class AmqpConnectionSettingsTest
 * @package JTL\Nachricht\Transport\Amqp
 */
#[CoversClass(AmqpConnectionSettings::class)]
class AmqpConnectionSettingsTest extends TestCase
{
    public function testCanBeCreated(): void
    {
        $host = uniqid('host', true);
        $port = random_int(1, 100);
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

    /**
     * A connection without a heartbeat cannot tell a silent broker from a quiet one, so the
     * default is on. The read/write timeout follows it, while connection and RPC timeouts stay
     * short.
     */
    public function testHeartbeatIsOnByDefault(): void
    {
        $settings = new AmqpConnectionSettings('localhost', 5672, '15672', 'guest', 'guest');

        $this->assertSame(AmqpConnectionSettings::DEFAULT_HEARTBEAT, $settings->getHeartbeat());
        $this->assertSame(3.0, $settings->getTimeout());
        $this->assertSame(
            AmqpConnectionSettings::DEFAULT_HEARTBEAT * 2.0,
            $settings->getReadWriteTimeout(),
        );
        $this->assertSame(3.0, $settings->getChannelRpcTimeout());
    }

    /**
     * The escape hatch: an explicit zero switches the heartbeat off and restores exactly what
     * this class did before it knew about heartbeats - every timeout equal to $timeout.
     */
    public function testExplicitZeroDisablesTheHeartbeatAndRestoresTheOldTimeouts(): void
    {
        $settings = new AmqpConnectionSettings(
            'localhost',
            5672,
            '15672',
            'guest',
            'guest',
            heartbeat: 0,
        );

        $this->assertSame(0, $settings->getHeartbeat());
        $this->assertSame(3.0, $settings->getReadWriteTimeout());
        $this->assertSame(3.0, $settings->getChannelRpcTimeout());
    }

    public function testReadWriteTimeoutIsWidenedToTwiceTheHeartbeat(): void
    {
        $settings = new AmqpConnectionSettings(
            'localhost',
            5672,
            '15672',
            'guest',
            'guest',
            timeout: 3.0,
            heartbeat: 30,
        );

        // php-amqplib refuses a read/write timeout below twice the heartbeat, so it has to grow
        // even though $timeout stayed small.
        $this->assertSame(60.0, $settings->getReadWriteTimeout());
        $this->assertSame(3.0, $settings->getTimeout(), 'the connection timeout must stay short');
        $this->assertSame(3.0, $settings->getChannelRpcTimeout(), 'the rpc timeout must stay short');
    }

    public function testExplicitTimeoutsWin(): void
    {
        $settings = new AmqpConnectionSettings(
            'localhost',
            5672,
            '15672',
            'guest',
            'guest',
            timeout: 3.0,
            heartbeat: 10,
            readWriteTimeout: 45.0,
            channelRpcTimeout: 7.5,
        );

        $this->assertSame(45.0, $settings->getReadWriteTimeout());
        $this->assertSame(7.5, $settings->getChannelRpcTimeout());
    }

    public function testRejectsAReadWriteTimeoutTooSmallForTheHeartbeat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('at least twice the heartbeat');

        // Fail here with a readable message instead of deep inside php-amqplib's StreamIO.
        new AmqpConnectionSettings(
            'localhost',
            5672,
            '15672',
            'guest',
            'guest',
            heartbeat: 30,
            readWriteTimeout: 10.0,
        );
    }

    public function testRejectsANegativeHeartbeat(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AmqpConnectionSettings('localhost', 5672, '15672', 'guest', 'guest', heartbeat: -1);
    }
}
