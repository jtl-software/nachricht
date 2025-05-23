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
     * @var ListenerProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $listenerProvider;

    /**
     * @var AmqpDispatcher
     */
    private $dispatcher;

    /**
     * @var Message|\PHPUnit\Framework\MockObject\Stub
     */
    private $event;

    public function setUp(): void
    {
        $this->listenerProvider = $this->createMock(ListenerProvider::class);
        $this->event = $this->createStub(Message::class);
        $this->dispatcher = new AmqpDispatcher($this->listenerProvider);
    }

    public function testCanDispatch(): void
    {
        $listener = function (object $message) {
            $this->assertEquals($this->event, $message);
        };

        $this->listenerProvider
            ->expects($this->once())
            ->method('getListenersForMessage')
            ->with($this->event)
            ->willReturn([$listener]);

        $this->dispatcher->dispatch($this->event);
    }
}
