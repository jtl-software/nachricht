<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Listener;

use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Contract\Listener\Listener;
use JTL\Nachricht\Listener\Cache\ListenerCache;
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

    /**
     * @var ListenerCache|Mockery\MockInterface
     */
    private $listenerCache;

    public function setUp(): void
    {
        $this->container = Mockery::mock(ContainerInterface::class);
        $this->event = Mockery::mock(Event::class);
        $this->listener = Mockery::mock(Listener::class);
        $this->listenerCache = Mockery::mock(ListenerCache::class);
        $this->listenerProvider = new ListenerProvider($this->container, $this->listenerCache);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetListenersForEvent(): void
    {
        $listenerList = [
            [
                'listenerClass' => 'FooListener',
                'method' => 'listen'
            ]
        ];

        $this->listenerCache->shouldReceive('getListenerListForEvent')
            ->once()
            ->andReturn($listenerList);

        $this->container->shouldReceive('get')
            ->with('FooListener')
            ->once()
            ->andReturn($this->listener);

        foreach ($this->listenerProvider->getListenersForEvent($this->event) as $listenerClosure) {
            $this->assertTrue(is_callable($listenerClosure));
        }
    }
}
