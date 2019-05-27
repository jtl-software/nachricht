<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Emitter;

use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Contract\Listener\Listener;
use JTL\Nachricht\Listener\ListenerProvider;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class DirectEmitterTest
 * @package JTL\Nachricht\Emitter
 *
 * @covers \JTL\Nachricht\Emitter\DirectEmitter
 */
class DirectEmitterTest extends TestCase
{

    /**
     * @var ListenerProvider|Mockery\MockInterface
     */
    private $listenerProvider;

    /**
     * @var Listener|Mockery\MockInterface
     */
    private $listener;

    /**
     * @var Event|Mockery\MockInterface
     */
    private $event;

    /**
     * @var DirectEmitter
     */
    private $directEmitter;

    public function setUp(): void
    {
        $this->listenerProvider = Mockery::mock(ListenerProvider::class);
        $this->listener = Mockery::mock(Listener::class);
        $this->event = Mockery::mock(Event::class);
        $this->directEmitter = new DirectEmitter($this->listenerProvider);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testCanEmit()
    {
        $this->listenerProvider->shouldReceive('getListenersForEvent')
            ->with($this->event)
            ->once()
            ->andReturn([$this->listener]);

        $this->listener->shouldReceive('__invoke')
            ->with($this->event)
            ->once();

        $this->directEmitter->emit($this->event);

        //For coverage
        $this->assertTrue(true);
    }
}
