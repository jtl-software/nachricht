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

    /**
     * @var MessageSerializer|Mockery\MockInterface
     */
    private $messageSerializer;

    /**
     * @var AmqpTransportFactory
     */
    private $factory;

    /**
     * @var ListenerProvider|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $provider;

    public function setUp(): void
    {
        $this->eventSerializer = Mockery::mock(MessageSerializer::class);
        $this->factory = new AmqpTransportFactory();
        $this->provider = Mockery::mock(ListenerProvider::class);
    }

    public function testCreateTransport(): void
    {
        Mockery::mock(AmqpTransport::class);

        $connectionSettings = [
            'host' => 'localhost',
            'port' => (string)random_int(1, 123),
            'httpPort' => (string)random_int(1, 123),
            'user' => 'guest',
            'password' => 'guest'
        ];

        $transport = $this->factory->createTransport($connectionSettings, $this->eventSerializer, $this->provider);
        $this->assertInstanceOf(AmqpTransport::class, $transport);
    }
}
