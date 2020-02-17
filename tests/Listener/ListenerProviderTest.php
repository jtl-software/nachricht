<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Listener;

use JTL\Nachricht\Contract\Message\Message;
use JTL\Nachricht\Contract\Hook\AfterMessageErrorHook;
use JTL\Nachricht\Contract\Hook\AfterMessageHook;
use JTL\Nachricht\Contract\Hook\BeforeMessageHook;
use JTL\Nachricht\Contract\Listener\Listener;
use JTL\Nachricht\Message\Cache\MessageCache;
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
     * @var Message|Mockery\MockInterface
     */
    private $event;

    /**
     * @var Listener|Mockery\MockInterface
     */
    private $listener;

    /**
     * @var MessageCache|Mockery\MockInterface
     */
    private $listenerCache;

    public function setUp(): void
    {
        $this->container = Mockery::mock(ContainerInterface::class);
        $this->event = Mockery::mock(Message::class);
        $this->listener = new TestListener();
        $this->listenerCache = Mockery::mock(MessageCache::class);
        $this->listenerProvider = new ListenerProvider($this->container, $this->listenerCache);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetListenersForMessage(): void
    {
        $listenerList = [
            [
                'listenerClass' => 'FooListener',
                'method' => 'listen'
            ]
        ];

        $this->listenerCache->shouldReceive('getListenerListForMessage')
            ->once()
            ->andReturn($listenerList);

        $this->container->shouldReceive('get')
            ->with('FooListener')
            ->once()
            ->andReturn($this->listener);

        foreach ($this->listenerProvider->getListenersForMessage($this->event) as $listenerClosure) {
            $this->assertTrue(is_callable($listenerClosure));
            $listenerClosure($this->event);
        }
    }

    public function dataProviderTestHooks(): array
    {
        return [
            [TestListenerWithBeforeMessageHook::class, ['setup']],
            [TestListenerWithAfterMessageHook::class, ['after']],
            [TestListenerWithAfterAndBeforeMessageHook::class, ['setup', 'after']],
        ];
    }

    /**
     * @dataProvider dataProviderTestHooks
     */
    public function testCanHandleBeforeMessageHook($testlistenerClass, array $hookList)
    {
        $eventStub = $this->createStub(Message::class);

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

        $eventCacheMock = $this->createMock(MessageCache::class);
        $eventCacheMock->expects($this->once())->method('getListenerListForMessage')
            ->willReturn([
                [
                    'listenerClass' => 'TestListener',
                    'method' => 'listen'
                ]
            ]);
        $provider = new ListenerProvider($containerMock, $eventCacheMock);
        foreach ($provider->getListenersForMessage($eventStub) as $listenerClosure) {
            $listenerClosure($eventStub);
        }
    }

    public function testAfterMessageErrorHook()
    {
        $eventStub = $this->createStub(Message::class);
        $throwableStub = $this->createStub(\Throwable::class);

        $testListenerMock = $this->createMock(TestListenerWithErrorAndAfterMessageHook::class);
        $testListenerMock->expects($this->once())->method('onError')->with($eventStub, $throwableStub);
        $testListenerMock->expects($this->once())->method('after')->with($eventStub);
        $testListenerMock->expects($this->once())->method('listen')->with($eventStub)
            ->willThrowException($throwableStub);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->once())->method('get')
            ->with('TestListener')
            ->willReturn($testListenerMock);

        $eventCacheMock = $this->createMock(MessageCache::class);
        $eventCacheMock->expects($this->once())->method('getListenerListForMessage')
            ->willReturn([
                [
                    'listenerClass' => 'TestListener',
                    'method' => 'listen'
                ]
            ]);
        $provider = new ListenerProvider($containerMock, $eventCacheMock);

        foreach ($provider->getListenersForMessage($eventStub) as $listenerClosure) {
            $listenerClosure($eventStub);
        }
    }

    public function testOnErrorHookCanThrowExceptionAndAfterHookIsAlsoExecuted()
    {
        $eventStub = $this->createStub(Message::class);
        $throwableStub = $this->createStub(\Throwable::class);

        $testListenerMock = $this->createMock(TestListenerWithErrorAndAfterMessageHook::class);
        $testListenerMock->expects($this->once())->method('onError')
            ->with($eventStub, $throwableStub)->willThrowException($throwableStub);
        $testListenerMock->expects($this->once())->method('after')->with($eventStub);
        $testListenerMock->expects($this->once())->method('listen')->with($eventStub)
            ->willThrowException($throwableStub);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->once())->method('get')
            ->with('TestListener')
            ->willReturn($testListenerMock);

        $eventCacheMock = $this->createMock(MessageCache::class);
        $eventCacheMock->expects($this->once())->method('getListenerListForMessage')
            ->willReturn([
                [
                    'listenerClass' => 'TestListener',
                    'method' => 'listen'
                ]
            ]);
        $provider = new ListenerProvider($containerMock, $eventCacheMock);

        $this->expectException(\Throwable::class);
        foreach ($provider->getListenersForMessage($eventStub) as $listenerClosure) {
            $listenerClosure($eventStub);
        }
    }

    public function testExceptionIsThrown()
    {
        $eventStub = $this->createStub(Message::class);
        $throwableStub = $this->createStub(\Throwable::class);

        $testListenerMock = $this->createMock(TestListener::class);
        $testListenerMock->expects($this->once())->method('listen')->with($eventStub)
            ->willThrowException($throwableStub);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->once())->method('get')
            ->with('TestListener')
            ->willReturn($testListenerMock);

        $eventCacheMock = $this->createMock(MessageCache::class);
        $eventCacheMock->expects($this->once())->method('getListenerListForMessage')
            ->willReturn([
                [
                    'listenerClass' => 'TestListener',
                    'method' => 'listen'
                ]
            ]);
        $provider = new ListenerProvider($containerMock, $eventCacheMock);

        $this->expectException(\Throwable::class);
        foreach ($provider->getListenersForMessage($eventStub) as $listenerClosure) {
            $listenerClosure($eventStub);
        }
    }
}

class TestListener implements Listener
{
    public function listen(Message $event): Message
    {
        return $event;
    }
}

class TestListenerWithBeforeMessageHook extends TestListener implements BeforeMessageHook
{
    public function setup(Message $event): void
    {
    }
}

class TestListenerWithAfterMessageHook extends TestListener implements AfterMessageHook
{
    public function after(Message $event): void
    {
    }
}

class TestListenerWithAfterAndBeforeMessageHook extends TestListener implements AfterMessageHook, BeforeMessageHook
{
    public function setup(Message $event): void
    {
    }

    public function after(Message $event): void
    {
    }
}

class TestListenerWithErrorAndAfterMessageHook extends TestListener implements AfterMessageHook, AfterMessageErrorHook
{
    public function onError(Message $event, \Throwable $throwable): void
    {
    }

    public function after(Message $event): void
    {
    }
}
