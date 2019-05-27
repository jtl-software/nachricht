<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Emitter;

use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Transport\RabbitMq\RabbitMqTransport;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class RabbitMqEmitterTest
 * @package JTL\Nachricht\Emitter
 *
 * @covers \JTL\Nachricht\Emitter\RabbitMqEmitter
 */
class RabbitMqEmitterTest extends TestCase
{
    /**
     * @var RabbitMqTransport|Mockery\MockInterface
     */
    private $rabbitMqTransport;

    /**
     * @var RabbitMqEmitter
     */
    private $rabbitMqEmitter;

    /**
     * @var Event|Mockery\MockInterface
     */
    private $event;

    public function setUp(): void
    {
        $this->rabbitMqTransport = Mockery::mock(RabbitMqTransport::class);
        $this->event = Mockery::mock(Event::class);
        $this->rabbitMqEmitter = new RabbitMqEmitter($this->rabbitMqTransport);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testEmit(): void
    {
        $this->rabbitMqTransport->shouldReceive('publish')
            ->with($this->event)
            ->once();

        $this->rabbitMqEmitter->emit($this->event);

        //For coverage
        $this->assertTrue(true);
    }
}
