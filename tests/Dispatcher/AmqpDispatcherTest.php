<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Dispatcher;

use JTL\Nachricht\Contract\Message\Message;
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
     * @var Message|Mockery\MockInterface
     */
    private $event;

    public function setUp(): void
    {
        $this->listenerProvider = Mockery::mock(ListenerProvider::class);
        $this->event = Mockery::mock(Message::class);
        $this->dispatcher = new AmqpDispatcher($this->listenerProvider);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testCanDispatch(): void
    {
        $listener = function (object $event) {
            $this->assertEquals($this->event, $event);
        };

        $this->listenerProvider->shouldReceive('getListenersForMessage')
            ->with($this->event)
            ->once()
            ->andReturn([$listener]);

        $this->dispatcher->dispatch($this->event);
    }
}
