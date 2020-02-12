<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Listener;

use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Contract\Hook\AfterEventErrorHook;
use JTL\Nachricht\Contract\Hook\AfterEventHook;
use JTL\Nachricht\Contract\Hook\BeforeEventHook;
use JTL\Nachricht\Contract\Listener\Listener;
use JTL\Nachricht\Event\Cache\EventCache;
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
     * @var EventCache|Mockery\MockInterface
     */
    private $listenerCache;

    public function setUp(): void
    {
        $this->container = Mockery::mock(ContainerInterface::class);
        $this->event = Mockery::mock(Event::class);
        $this->listener = new TestListener();
        $this->listenerCache = Mockery::mock(EventCache::class);
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
            $listenerClosure($this->event);
        }
    }

    public function dataProviderTestHooks(): array
    {
        return [
            [TestListenerWithBeforeEventHook::class, ['setup']],
            [TestListenerWithAfterEventHook::class, ['after']],
            [TestListenerWithAfterAndBeforeEventHook::class, ['setup', 'after']],
        ];
    }

    /**
     * @dataProvider dataProviderTestHooks
     */
    public function testCanHandleBeforeEventHook($testlistenerClass, array $hookList)
    {
        $eventStub = $this->createStub(Event::class);

        $testListenerMock = $this->createMock($testlistenerClass);
        foreach ($hookList as $methodToCall) {
            $testListenerMock->expects($this->once())->method($methodToCall)
                ->with($eventStub, );
        }
        $testListenerMock->expects($this->once())->method('listen')->with($eventStub);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->once())->method('get')
            ->with('TestListener')
            ->willReturn($testListenerMock);

        $eventCacheMock = $this->createMock(EventCache::class);
        $eventCacheMock->expects($this->once())->method('getListenerListForEvent')
            ->willReturn([
                [
                    'listenerClass' => 'TestListener',
                    'method' => 'listen'
                ]
            ]);
        $provider = new ListenerProvider($containerMock, $eventCacheMock);
        foreach ($provider->getListenersForEvent($eventStub) as $listenerClosure) {
            $listenerClosure($eventStub);
        }
    }

    public function testAfterEventErrorHook()
    {
        $eventStub = $this->createStub(Event::class);
        $throwableStub = $this->createStub(\Throwable::class);

        $testListenerMock = $this->createMock(TestListenerWithErrorAndAfterEventHook::class);
        $testListenerMock->expects($this->once())->method('onError')->with($eventStub, $throwableStub);
        $testListenerMock->expects($this->once())->method('after')->with($eventStub);
        $testListenerMock->expects($this->once())->method('listen')->with($eventStub)
            ->willThrowException($throwableStub);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->once())->method('get')
            ->with('TestListener')
            ->willReturn($testListenerMock);

        $eventCacheMock = $this->createMock(EventCache::class);
        $eventCacheMock->expects($this->once())->method('getListenerListForEvent')
            ->willReturn([
                [
                    'listenerClass' => 'TestListener',
                    'method' => 'listen'
                ]
            ]);
        $provider = new ListenerProvider($containerMock, $eventCacheMock);

        foreach ($provider->getListenersForEvent($eventStub) as $listenerClosure) {
            $listenerClosure($eventStub);
        }
    }

    public function testOnErrorHookCanThrowExceptionAndAfterHookIsAlsoExecuted()
    {
        $eventStub = $this->createStub(Event::class);
        $throwableStub = $this->createStub(\Throwable::class);

        $testListenerMock = $this->createMock(TestListenerWithErrorAndAfterEventHook::class);
        $testListenerMock->expects($this->once())->method('onError')
            ->with($eventStub, $throwableStub)->willThrowException($throwableStub);
        $testListenerMock->expects($this->once())->method('after')->with($eventStub);
        $testListenerMock->expects($this->once())->method('listen')->with($eventStub)
            ->willThrowException($throwableStub);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->once())->method('get')
            ->with('TestListener')
            ->willReturn($testListenerMock);

        $eventCacheMock = $this->createMock(EventCache::class);
        $eventCacheMock->expects($this->once())->method('getListenerListForEvent')
            ->willReturn([
                [
                    'listenerClass' => 'TestListener',
                    'method' => 'listen'
                ]
            ]);
        $provider = new ListenerProvider($containerMock, $eventCacheMock);

        $this->expectException(\Throwable::class);
        foreach ($provider->getListenersForEvent($eventStub) as $listenerClosure) {
            $listenerClosure($eventStub);
        }
    }

    public function testExceptionIsThrown()
    {
        $eventStub = $this->createStub(Event::class);
        $throwableStub = $this->createStub(\Throwable::class);

        $testListenerMock = $this->createMock(TestListener::class);
        $testListenerMock->expects($this->once())->method('listen')->with($eventStub)
            ->willThrowException($throwableStub);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->once())->method('get')
            ->with('TestListener')
            ->willReturn($testListenerMock);

        $eventCacheMock = $this->createMock(EventCache::class);
        $eventCacheMock->expects($this->once())->method('getListenerListForEvent')
            ->willReturn([
                [
                    'listenerClass' => 'TestListener',
                    'method' => 'listen'
                ]
            ]);
        $provider = new ListenerProvider($containerMock, $eventCacheMock);

        $this->expectException(\Throwable::class);
        foreach ($provider->getListenersForEvent($eventStub) as $listenerClosure) {
            $listenerClosure($eventStub);
        }
    }
}

class TestListener implements Listener
{
    public function listen(Event $event): Event
    {
        return $event;
    }
}

class TestListenerWithBeforeEventHook extends TestListener implements BeforeEventHook
{
    public function setup(Event $event): void
    {
    }
}

class TestListenerWithAfterEventHook extends TestListener implements AfterEventHook
{
    public function after(Event $event): void
    {
    }
}

class TestListenerWithAfterAndBeforeEventHook extends TestListener implements AfterEventHook, BeforeEventHook
{
    public function setup(Event $event): void
    {
    }

    public function after(Event $event): void
    {
    }
}

class TestListenerWithErrorAndAfterEventHook extends TestListener implements AfterEventHook, AfterEventErrorHook
{
    public function onError(Event $event, \Throwable $throwable): void
    {
    }

    public function after(Event $event): void
    {
    }
}
