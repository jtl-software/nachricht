<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Listener;

use JTL\Nachricht\Collection\StringCollection;
use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Contract\Listener\Listener;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Class ListenerProviderTest
 * @package JTL\Nachricht\Listener
 *
 * @covers \JTL\Nachricht\Listener\ListenerProvider
 */
class ListenerProviderTest extends TestCase
{
    /**
     * @var Mockery\MockInterface|ContainerInterface
     */
    private $container;

    /**
     * @var ListenerProvider
     */
    private $listenerProvider;

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
        $this->container = Mockery::mock(ContainerInterface::class);
        $this->event = Mockery::mock(Event::class);
        $this->listener = Mockery::mock(Listener::class);
        $this->listenerProvider = new ListenerProvider($this->container);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetListenersForEvent(): void
    {
        $randomListenerClass = uniqid('listenerClass', true);

        $this->event->shouldReceive('getListenerClassList')
            ->once()
            ->andReturn(StringCollection::from($randomListenerClass));

        $this->container->shouldReceive('get')
            ->with($randomListenerClass)
            ->once()
            ->andReturn($this->listener);

        foreach ($this->listenerProvider->getListenersForEvent($this->event) as $listener) {
            $this->assertEquals($this->listener, $listener);
        }
    }
}
