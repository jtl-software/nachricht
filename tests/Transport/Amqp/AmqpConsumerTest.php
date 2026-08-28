<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Transport\Amqp;

use PHPUnit\Framework\Attributes\CoversClass;
use Closure;
use Exception;
use JTL\Generic\StringCollection;
use JTL\Nachricht\Contract\Message\AmqpTransportableMessage;
use JTL\Nachricht\Dispatcher\AmqpDispatcher;
use JTL\Nachricht\Transport\SubscriptionSettings;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Psr\Log\NullLogger;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
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
 */
#[CoversClass(AmqpConsumer::class)]
class AmqpConsumerTest extends TestCase
{
    private AmqpTransport&MockObject $transport;
    private AmqpDispatcher&MockObject $dispatcher;
    private AmqpConsumer $consumer;
    private SubscriptionSettings&Stub $subscriptionSettings;
    private AmqpTransportableMessage&Stub $event;

    public function setUp(): void
    {
        $this->transport = $this->createMock(AmqpTransport::class);
        $this->event = $this->createStub(AmqpTransportableMessage::class);
        $this->dispatcher = $this->createMock(AmqpDispatcher::class);
        $this->subscriptionSettings = $this->createStub(SubscriptionSettings::class);

        $this->consumer = new AmqpConsumer($this->transport, $this->dispatcher);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testCanConsume(): void
    {
        $this->transport->expects(self::once())->method('subscribe')
            ->with($this->subscriptionSettings, self::isInstanceOf(Closure::class));

        $this->transport->expects(self::once())->method('poll')->willThrowException(new MockException());

        $this->expectException(Exception::class);

        $this->consumer->consume($this->subscriptionSettings);
    }

    /**
     * An idle queue makes every poll() time out. The ttl must still be honoured in that case,
     * otherwise a consumer with a ttl renews its subscription forever and never shuts down.
     * The once() expectations double as a hang guard: a regression calls poll() again and fails
     * the expectation instead of looping until the CI job is killed.
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testTtlIsHonouredWhenEveryPollTimesOut(): void
    {
        $subscriptionSettings = new SubscriptionSettings(StringCollection::from('some-queue'), ttl: 0);

        $this->transport->method('getConnectionSettings')->willReturn($this->settingsWithHeartbeat(0));
        $this->transport->expects(self::once())->method('poll')
            ->willThrowException(new AMQPTimeoutException());
        $this->transport->expects(self::once())->method('renewSubscription')
            ->with($subscriptionSettings, self::isInstanceOf(Closure::class));

        // NullLogger: the default EchoLogger writes the shutdown notice to stdout, which trips
        // beStrictAboutOutputDuringTests.
        $consumer = new AmqpConsumer($this->transport, $this->dispatcher, new NullLogger());
        $consumer->consume($subscriptionSettings);
    }

    /**
     * With a heartbeat the connection detects an unresponsive broker on its own, so the
     * subscription must be left alone on an idle timeout - renewing it there is what loses a
     * delivery that lands during the cancel/consume window and strands it in UNACKED.
     */
    #[AllowMockObjectsWithoutExpectations]
    public function testSubscriptionIsNotRenewedWhenAHeartbeatIsConfigured(): void
    {
        $subscriptionSettings = new SubscriptionSettings(StringCollection::from('some-queue'), ttl: 0);

        $this->transport->method('getConnectionSettings')->willReturn($this->settingsWithHeartbeat(30));
        $this->transport->expects(self::once())->method('poll')
            ->willThrowException(new AMQPTimeoutException());
        $this->transport->expects(self::never())->method('renewSubscription');

        $consumer = new AmqpConsumer($this->transport, $this->dispatcher, new NullLogger());
        $consumer->consume($subscriptionSettings);
    }

    private function settingsWithHeartbeat(int $heartbeat): AmqpConnectionSettings
    {
        return new AmqpConnectionSettings('localhost', 5672, '15672', 'guest', 'guest', heartbeat: $heartbeat);
    }

    #[AllowMockObjectsWithoutExpectations]
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
