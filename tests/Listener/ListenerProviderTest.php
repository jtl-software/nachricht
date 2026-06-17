<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Listener;

use PHPUnit\Framework\Attributes\CoversClass;
use Throwable;
use JTL\Nachricht\Contract\Message\Message;
use JTL\Nachricht\Contract\Hook\AfterMessageErrorHook;
use JTL\Nachricht\Contract\Hook\AfterMessageHook;
use JTL\Nachricht\Contract\Hook\BeforeMessageHook;
use JTL\Nachricht\Contract\Listener\Listener;
use JTL\Nachricht\Message\Cache\MessageCache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Class ListenerProviderTest
 * @package JTL\Nachricht\Listener
 */
#[CoversClass(ListenerProvider::class)]
class ListenerProviderTest extends TestCase
{
    private Message $message;
    private Listener $listener;

    public function setUp(): void
    {
        $this->message = $this->createStub(Message::class);
        $this->listener = new TestListener();
    }


    public function testGetListenersForMessage(): void
    {
        $listenerList = [
            [
                'listenerClass' => 'FooListener',
                'method' => 'listen'
            ]
        ];

        $listenerCacheMock = $this->createMock(MessageCache::class);
        $listenerCacheMock->expects($this->once())
            ->method('getListenerListForMessage')
            ->willReturn($listenerList);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->once())
            ->method('get')
            ->with('FooListener')
            ->willReturn($this->listener);

        $provider = new ListenerProvider($containerMock, $listenerCacheMock);

        foreach ($provider->getListenersForMessage($this->message) as $listenerClosure) {
            $this->assertIsCallable($listenerClosure);
            $listenerClosure($this->message);
        }
    }

    public static function dataProviderTestHooks(): array
    {
        return [
            [TestListenerWithBeforeMessageHook::class, ['setup']],
            [TestListenerWithAfterMessageHook::class, ['after']],
            [TestListenerWithAfterAndBeforeMessageHook::class, ['setup', 'after']],
        ];
    }

    #[DataProvider('dataProviderTestHooks')]
    public function testCanHandleBeforeMessageHook(string $testlistenerClass, array $hookList)
    {
        $messageStub = $this->createStub(Message::class);

        $testListenerMock = $this->createMock($testlistenerClass);
        foreach ($hookList as $methodToCall) {
            $testListenerMock->expects($this->once())->method($methodToCall)
                ->with($messageStub, );
        }
        $testListenerMock->expects($this->once())->method('listen')->with($messageStub);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->once())->method('get')
            ->with('TestListener')
            ->willReturn($testListenerMock);

        $messageCacheMock = $this->createMock(MessageCache::class);
        $messageCacheMock->expects($this->once())->method('getListenerListForMessage')
            ->willReturn([
                [
                    'listenerClass' => 'TestListener',
                    'method' => 'listen'
                ]
            ]);
        $provider = new ListenerProvider($containerMock, $messageCacheMock);
        foreach ($provider->getListenersForMessage($messageStub) as $listenerClosure) {
            $listenerClosure($messageStub);
        }
    }

    public function testAfterMessageErrorHook(): void
    {
        $messageStub = $this->createStub(Message::class);
        $throwableStub = $this->createStub(Throwable::class);

        $testListenerMock = $this->createMock(TestListenerWithErrorAndAfterMessageHook::class);
        $testListenerMock->expects($this->once())->method('onError')->with($messageStub, $throwableStub);
        $testListenerMock->expects($this->once())->method('after')->with($messageStub);
        $testListenerMock->expects($this->once())->method('listen')->with($messageStub)
            ->willThrowException($throwableStub);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->once())->method('get')
            ->with('TestListener')
            ->willReturn($testListenerMock);

        $messageCacheMock = $this->createMock(MessageCache::class);
        $messageCacheMock->expects($this->once())->method('getListenerListForMessage')
            ->willReturn([
                [
                    'listenerClass' => 'TestListener',
                    'method' => 'listen'
                ]
            ]);
        $provider = new ListenerProvider($containerMock, $messageCacheMock);

        foreach ($provider->getListenersForMessage($messageStub) as $listenerClosure) {
            $listenerClosure($messageStub);
        }
    }

    public function testOnErrorHookCanThrowExceptionAndAfterHookIsAlsoExecuted()
    {
        $messageStub = $this->createStub(Message::class);
        $throwableStub = $this->createStub(Throwable::class);

        $testListenerMock = $this->createMock(TestListenerWithErrorAndAfterMessageHook::class);
        $testListenerMock->expects($this->once())->method('onError')
            ->with($messageStub, $throwableStub)->willThrowException($throwableStub);
        $testListenerMock->expects($this->once())->method('after')->with($messageStub);
        $testListenerMock->expects($this->once())->method('listen')->with($messageStub)
            ->willThrowException($throwableStub);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->once())->method('get')
            ->with('TestListener')
            ->willReturn($testListenerMock);

        $messageCacheMock = $this->createMock(MessageCache::class);
        $messageCacheMock->expects($this->once())->method('getListenerListForMessage')
            ->willReturn([
                [
                    'listenerClass' => 'TestListener',
                    'method' => 'listen'
                ]
            ]);
        $provider = new ListenerProvider($containerMock, $messageCacheMock);

        $this->expectException(Throwable::class);
        foreach ($provider->getListenersForMessage($messageStub) as $listenerClosure) {
            $listenerClosure($messageStub);
        }
    }

    public function testExceptionIsThrown()
    {
        $messageStub = $this->createStub(Message::class);
        $throwableStub = $this->createStub(Throwable::class);

        $testListenerMock = $this->createMock(TestListener::class);
        $testListenerMock->expects($this->once())->method('listen')->with($messageStub)
            ->willThrowException($throwableStub);

        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->once())->method('get')
            ->with('TestListener')
            ->willReturn($testListenerMock);

        $messageCacheMock = $this->createMock(MessageCache::class);
        $messageCacheMock->expects($this->once())->method('getListenerListForMessage')
            ->willReturn([
                [
                    'listenerClass' => 'TestListener',
                    'method' => 'listen'
                ]
            ]);
        $provider = new ListenerProvider($containerMock, $messageCacheMock);

        $this->expectException(Throwable::class);
        foreach ($provider->getListenersForMessage($messageStub) as $listenerClosure) {
            $listenerClosure($messageStub);
        }
    }
}

class TestListener implements Listener
{
    public function listen(Message $message): Message
    {
        return $message;
    }
}

class TestListenerWithBeforeMessageHook extends TestListener implements BeforeMessageHook
{
    public function setup(Message $message): void
    {
    }
}

class TestListenerWithAfterMessageHook extends TestListener implements AfterMessageHook
{
    public function after(Message $message): void
    {
    }
}

class TestListenerWithAfterAndBeforeMessageHook extends TestListener implements AfterMessageHook, BeforeMessageHook
{
    public function setup(Message $message): void
    {
    }

    public function after(Message $message): void
    {
    }
}

class TestListenerWithErrorAndAfterMessageHook extends TestListener implements AfterMessageHook, AfterMessageErrorHook
{
    public function onError(Message $message, Throwable $throwable): void
    {
    }

    public function after(Message $message): void
    {
    }
}
