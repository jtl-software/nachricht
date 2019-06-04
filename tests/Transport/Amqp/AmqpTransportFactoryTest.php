<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Transport\Amqp;

use JTL\Nachricht\Contract\Serializer\EventSerializer;
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
     * @var EventSerializer|Mockery\MockInterface
     */
    private $eventSerializer;

    /**
     * @var AmqpTransportFactory
     */
    private $factory;

    public function setUp(): void
    {
        $this->eventSerializer = Mockery::mock(EventSerializer::class);
        $this->factory = new AmqpTransportFactory();
    }

    public function testCreateTransport(): void
    {
        Mockery::mock(AmqpTransport::class);

        $connectionSettings = [
            'host' => 'localhost',
            'port' => (string)random_int(1, 123),
            'user' => 'guest',
            'password' => 'guest'
        ];

        $transport = $this->factory->createTransport($connectionSettings, $this->eventSerializer);
        $this->assertInstanceOf(AmqpTransport::class, $transport);
    }
}
