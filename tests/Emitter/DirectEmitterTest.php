<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Emitter;

use JTL\Nachricht\Contract\Event\Event;
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
        $this->event = Mockery::mock(Event::class);
        $this->directEmitter = new DirectEmitter($this->listenerProvider);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testCanEmit()
    {
        $listener = function (object $event) {
            $this->assertEquals($this->event, $event);
        };

        $this->listenerProvider->shouldReceive('getListenersForEvent')
            ->with($this->event)
            ->once()
            ->andReturn([$listener]);

        $this->directEmitter->emit($this->event);
    }
}
