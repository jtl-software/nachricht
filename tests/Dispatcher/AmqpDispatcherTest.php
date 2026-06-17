<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Dispatcher;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use JTL\Nachricht\Contract\Message\Message;
use JTL\Nachricht\Listener\ListenerProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class AmqpDispatcherTest
 * @package JTL\Nachricht\Dispatcher
 */
#[CoversClass(AmqpDispatcher::class)]
class AmqpDispatcherTest extends TestCase
{
    /**
     * @var ListenerProvider|MockObject
     */
    private $listenerProvider;

    /**
     * @var AmqpDispatcher
     */
    private $dispatcher;

    /**
     * @var Message|Stub
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
