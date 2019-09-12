<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Dispatcher;

use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Listener\ListenerProvider;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class AmqpDispatcherTest
 * @package JTL\Nachricht\Dispatcher
 *
 * @covers \JTL\Nachricht\Dispatcher\AmqpDispatcher
 */
class AmqpDispatcherTest extends TestCase
{
    /**
     * @var ListenerProvider|Mockery\MockInterface
     */
    private $listenerProvider;

    /**
     * @var AmqpDispatcher
     */
    private $dispatcher;

    /**
     * @var Event|Mockery\MockInterface
     */
    private $event;

    /**
     * @var \Closure|Mockery\MockInterface
     */
    private $listener;


    public function setUp(): void
    {
        $this->listenerProvider = Mockery::mock(ListenerProvider::class);
        $this->listener = Mockery::on(\Closure::class);
        $this->event = Mockery::mock(Event::class);
        $this->dispatcher = new AmqpDispatcher($this->listenerProvider);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testCanDispatch(): void
    {
        $this->listenerProvider->shouldReceive('getListenersForEvent')
            ->with($this->event)
            ->once()
            ->andReturn([$this->listener]);

        $this->listener->shouldReceive('__invoke')
            ->with($this->event)
            ->once();

        $this->dispatcher->dispatch($this->event);

        //For coverage
        $this->assertTrue(true);
    }
}
