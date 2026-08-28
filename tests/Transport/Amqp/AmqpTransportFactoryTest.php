<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Transport\Amqp;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use JTL\Nachricht\Contract\Serializer\MessageSerializer;
use JTL\Nachricht\Listener\ListenerProvider;
use Mockery;
use PHPUnit\Framework\MockObject\Stub\Stub;
use PHPUnit\Framework\TestCase;

/**
 * Class AmqpTransportFactoryTest
 * @package JTL\Nachricht\Transport\Amqp
 */
#[CoversClass(AmqpTransportFactory::class)]
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
class AmqpTransportFactoryTest extends TestCase
{
    private AmqpTransportFactory $factory;

    private \PHPUnit\Framework\MockObject\Stub&AmqpConnectionFactory $connectionFactory;

    public function setUp(): void
    {
        $this->connectionFactory = $this->createStub(AmqpConnectionFactory::class);
        $this->factory = new AmqpTransportFactory($this->connectionFactory);
    }

    public function testCreateTransport(): void
    {
        $connectionSettings = [
            'host' => 'localhost',
            'port' => (string)random_int(1, 123),
            'httpPort' => (string)random_int(1, 123),
            'user' => 'guest',
            'password' => 'guest'
        ];

        $transport = $this->factory->createTransport(
            $connectionSettings,
            self::createStub(MessageSerializer::class),
            self::createStub(ListenerProvider::class)
        );
        $this->assertInstanceOf(AmqpTransport::class, $transport);
    }

    public function testConnectionSettingsDefaultToAHeartbeat(): void
    {
        $transport = $this->factory->createTransport(
            ['host' => 'localhost', 'port' => '5672', 'httpPort' => '15672', 'user' => 'guest', 'password' => 'guest'],
            self::createStub(MessageSerializer::class),
            self::createStub(ListenerProvider::class),
        );

        $settings = $transport->getConnectionSettings();
        $this->assertSame(AmqpConnectionSettings::DEFAULT_HEARTBEAT, $settings->getHeartbeat());
        $this->assertSame(AmqpConnectionSettings::DEFAULT_HEARTBEAT * 2.0, $settings->getReadWriteTimeout());
    }

    public function testHeartbeatCanBeSwitchedOffThroughTheSettingsArray(): void
    {
        $transport = $this->factory->createTransport(
            [
                'host' => 'localhost',
                'port' => '5672',
                'httpPort' => '15672',
                'user' => 'guest',
                'password' => 'guest',
                'heartbeat' => '0',
            ],
            self::createStub(MessageSerializer::class),
            self::createStub(ListenerProvider::class),
        );

        $settings = $transport->getConnectionSettings();
        $this->assertSame(0, $settings->getHeartbeat());
        $this->assertSame(3.0, $settings->getReadWriteTimeout());
    }

    public function testHeartbeatAndTimeoutsCanBeConfigured(): void
    {
        $transport = $this->factory->createTransport(
            [
                'host' => 'localhost',
                'port' => '5672',
                'httpPort' => '15672',
                'user' => 'guest',
                'password' => 'guest',
                'heartbeat' => '30',
                'channelRpcTimeout' => '5.5',
            ],
            self::createStub(MessageSerializer::class),
            self::createStub(ListenerProvider::class),
        );

        $settings = $transport->getConnectionSettings();
        $this->assertSame(30, $settings->getHeartbeat());
        $this->assertSame(60.0, $settings->getReadWriteTimeout(), 'must be widened for the heartbeat');
        $this->assertSame(5.5, $settings->getChannelRpcTimeout());
    }

    /**
     * An unset env var expands to an empty string in the settings array. That must mean "derive
     * it", not "zero" - a zero read/write timeout would be rejected once a heartbeat is set.
     */
    public function testEmptyOptionalSettingsAreTreatedAsAbsent(): void
    {
        $transport = $this->factory->createTransport(
            [
                'host' => 'localhost',
                'port' => '5672',
                'httpPort' => '15672',
                'user' => 'guest',
                'password' => 'guest',
                'heartbeat' => '15',
                'readWriteTimeout' => '',
                'channelRpcTimeout' => '',
            ],
            self::createStub(MessageSerializer::class),
            self::createStub(ListenerProvider::class),
        );

        $settings = $transport->getConnectionSettings();
        $this->assertSame(30.0, $settings->getReadWriteTimeout());
        $this->assertSame(3.0, $settings->getChannelRpcTimeout());
    }
}
