<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Transport\RabbitMq;

use JTL\Nachricht\Contract\Serializer\EventSerializer;
use JTL\Nachricht\Contract\Transport\EventTransport;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class RabbitMqTransportFactoryTest
 * @package JTL\Nachricht\Transport\RabbitMq
 *
 * @covers \JTL\Nachricht\Transport\RabbitMq\RabbitMqTransportFactory
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RabbitMqTransportFactoryTest extends TestCase
{

    /**
     * @var EventSerializer|Mockery\MockInterface
     */
    private $eventSerializer;

    /**
     * @var RabbitMqTransportFactory
     */
    private $factory;

    public function setUp(): void
    {
        $this->eventSerializer = Mockery::mock(EventSerializer::class);
        $this->factory = new RabbitMqTransportFactory();
    }

    public function testCreateTransport(): void
    {
        Mockery::mock('overload:' . RabbitMqTransport::class, EventTransport::class);

        $connectionSettings = [
            'host' => 'localhost',
            'port' => (string)random_int(1, 123),
            'user' => 'guest',
            'password' => 'guest'
        ];

        $transport = $this->factory->createTransport($connectionSettings, $this->eventSerializer);
        $this->assertInstanceOf(RabbitMqTransport::class, $transport);
    }
}
