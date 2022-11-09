<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Transport\Amqp;

use Closure;
use Exception;
use JTL\Nachricht\Contract\Message\AmqpTransportableMessage;
use JTL\Nachricht\Dispatcher\AmqpDispatcher;
use JTL\Nachricht\Transport\SubscriptionSettings;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class MockException extends Exception
{
}

/**
 * Class AmqpConsumerTest
 * @package JTL\Nachricht\Transport\Amqp
 *
 * @covers \JTL\Nachricht\Transport\Amqp\AmqpConsumer
 */
class AmqpConsumerTest extends TestCase
{
    private AmqpTransport&MockObject $transport;
    private AmqpDispatcher&MockObject $dispatcher;
    private AmqpConsumer $consumer;
    private SubscriptionSettings&Stub $subscriptionSettings;
    private AmqpTransportableMessage&MockObject $event;

    public function setUp(): void
    {
        $this->transport = $this->createMock(AmqpTransport::class);
        $this->event = $this->createMock(AmqpTransportableMessage::class);
        $this->dispatcher = $this->createMock(AmqpDispatcher::class);
        $this->subscriptionSettings = $this->createStub(SubscriptionSettings::class);

        $this->consumer = new AmqpConsumer($this->transport, $this->dispatcher);
    }

    public function testCanConsume(): void
    {
        $this->transport->expects(self::once())->method('subscribe')
            ->with($this->subscriptionSettings, self::isInstanceOf(Closure::class));

        $this->transport->expects(self::once())->method('poll')->willThrowException(new MockException());

        $this->expectException(Exception::class);

        $this->consumer->consume($this->subscriptionSettings);
    }

    public function testCallback(): void
    {
        $reflection = new ReflectionClass(AmqpConsumer::class);

        $method = $reflection->getMethod('createCallback');
        $method->setAccessible(true);

        $callback = $method->getClosure($this->consumer);

        $this->dispatcher->expects(self::once())->method('dispatch')
            ->with($this->event);

        $callback()($this->event);

        //For coverage
        $this->assertTrue(true);
    }
}
