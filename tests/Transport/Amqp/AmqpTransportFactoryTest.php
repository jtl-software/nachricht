<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Transport\Amqp;

use JTL\Nachricht\Contract\Serializer\MessageSerializer;
use JTL\Nachricht\Listener\ListenerProvider;
use Mockery;
use PHPUnit\Framework\MockObject\Stub\Stub;
use PHPUnit\Framework\TestCase;

/**
 * Class AmqpTransportFactoryTest
 * @package JTL\Nachricht\Transport\Amqp
 *
 * @covers \JTL\Nachricht\Transport\Amqp\AmqpTransportFactory
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class AmqpTransportFactoryTest extends TestCase
{
    private AmqpTransportFactory $factory;

    private \PHPUnit\Framework\MockObject\MockObject&AmqpConnectionFactory $connectionFactory;

    public function setUp(): void
    {
        $this->connectionFactory = $this->createMock(AmqpConnectionFactory::class);
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
}
