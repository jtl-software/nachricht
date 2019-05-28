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
use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Dispatcher\AmqpDispatcher;
use JTL\Nachricht\Transport\SubscriptionSettings;
use Mockery;
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
    /**
     * @var AmqpTransport|Mockery\MockInterface
     */
    private $transport;

    /**
     * @var AmqpDispatcher|Mockery\MockInterface
     */
    private $dispatcher;

    /**
     * @var AmqpConsumer
     */
    private $consumer;

    /**
     * @var SubscriptionSettings|Mockery\MockInterface
     */
    private $subscriptionSettings;

    /**
     * @var Event|Mockery\MockInterface
     */
    private $event;

    public function setUp(): void
    {
        $this->transport = Mockery::mock(AmqpTransport::class);
        $this->event = Mockery::mock(Event::class);
        $this->dispatcher = Mockery::mock(AmqpDispatcher::class);
        $this->subscriptionSettings = Mockery::mock(SubscriptionSettings::class);

        $this->consumer = new AmqpConsumer($this->transport, $this->dispatcher);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testCanConsume(): void
    {
        $this->transport->shouldReceive('subscribe')
            ->with($this->subscriptionSettings, Mockery::type(Closure::class))
            ->once();

        $this->transport->shouldReceive('poll')
            ->once()
            ->andThrow(new MockException());

        $this->expectException(Exception::class);

        $this->consumer->consume($this->subscriptionSettings);
    }

    public function testCallback(): void
    {
        $reflection = new ReflectionClass(AmqpConsumer::class);

        $method = $reflection->getMethod('createCallback');
        $method->setAccessible(true);

        $callback = $method->getClosure($this->consumer);

        $this->dispatcher->shouldReceive('dispatch')
            ->with($this->event)
            ->once();

        $callback()($this->event);

        //For coverage
        $this->assertTrue(true);
    }
}
