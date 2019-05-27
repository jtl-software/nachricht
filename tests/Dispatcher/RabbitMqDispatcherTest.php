<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Dispatcher;

use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Contract\Listener\Listener;
use JTL\Nachricht\Listener\ListenerProvider;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class RabbitMqDispatcherTest
 * @package JTL\Nachricht\Dispatcher
 *
 * @covers \JTL\Nachricht\Dispatcher\RabbitMqDispatcher
 */
class RabbitMqDispatcherTest extends TestCase
{
    /**
     * @var ListenerProvider|Mockery\MockInterface
     */
    private $listenerProvider;

    /**
     * @var RabbitMqDispatcher
     */
    private $dispatcher;

    /**
     * @var Event|Mockery\MockInterface
     */
    private $event;

    /**
     * @var Listener|Mockery\MockInterface
     */
    private $listener;


    public function setUp(): void
    {
        $this->listenerProvider = Mockery::mock(ListenerProvider::class);
        $this->listener = Mockery::mock(Listener::class);
        $this->event = Mockery::mock(Event::class);
        $this->dispatcher = new RabbitMqDispatcher($this->listenerProvider);
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
