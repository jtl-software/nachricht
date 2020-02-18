<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Transport\Amqp;

/**
 * Overwrite php error_log() function to avoid test output in phpunit
 */
function error_log($message)
{
    return;
}

use Closure;
use JTL\Generic\StringCollection;
use JTL\Nachricht\Contract\Message\AmqpTransportableMessage;
use JTL\Nachricht\Contract\Message\Message;
use JTL\Nachricht\Contract\Serializer\MessageSerializer;
use JTL\Nachricht\Listener\ListenerProvider;
use JTL\Nachricht\Serializer\Exception\DeserializationFailedException;
use JTL\Nachricht\Transport\SubscriptionSettings;
use Mockery;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class AmqpTransportTest
 * @package JTL\Nachricht\Transport\Amqp
 *
 * @covers \JTL\Nachricht\Transport\Amqp\AmqpTransport
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class AmqpTransportTest extends TestCase
{
    /**
     * @var AmqpConnectionSettings|Mockery\MockInterface
     */
    private $connectionSettings;

    /**
     * @var MessageSerializer|Mockery\MockInterface
     */
    private $serializer;

    /**
     * @var Mockery\MockInterface
     */
    private $amqpConnection;

    /**
     * @var AmqpTransport
     */
    private $transport;

    /**
     * @var Mockery\MockInterface|AMQPChannel
     */
    private $channel;

    /**
     * @var Message|Mockery\MockInterface
     */
    private $message;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @var string
     */
    private $exchange;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $port;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $vhost;

    /**
     * @var static
     */
    private $queueNameList;

    /**
     * @var SubscriptionSettings|Mockery\MockInterface
     */
    private $subscriptionSettings;

    /**
     * @var Mockery\MockInterface|AMQPMessage
     */
    private $amqpMessage;

    /**
     * @var ListenerProvider|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $listenerProvider;

    public function setUp(): void
    {
        $this->routingKey = uniqid('routingKey', true);
        $this->exchange = '';

        $this->host = uniqid('host', true);
        $this->port = uniqid('port', true);
        $this->user = uniqid('user', true);
        $this->password = uniqid('password', true);
        $this->vhost = uniqid('vhost', true);

        $this->queueNameList = StringCollection::from(AmqpTransport::MESSAGE_QUEUE_PREFIX . $this->routingKey);

        $this->connectionSettings = Mockery::mock(AmqpConnectionSettings::class, [
            'getHost' => $this->host,
            'getPort' => $this->port,
            'getUser' => $this->user,
            'getPassword' => $this->password,
            'getVhost' => $this->vhost,
        ]);

        $this->serializer = Mockery::mock(MessageSerializer::class);
        $this->channel = Mockery::mock(AMQPChannel::class);
        $this->amqpConnection = Mockery::mock('overload:' . AMQPStreamConnection::class, [
            'channel' => $this->channel
        ]);
        $this->message = Mockery::mock(AmqpTransportableMessage::class, [
            'getRoutingKey' => $this->routingKey,
            'getExchange' => $this->exchange,
            'getMaxRetryCount' => 3
        ]);

        $this->subscriptionSettings = Mockery::mock(SubscriptionSettings::class, [
            'getQueueNameList' => $this->queueNameList
        ]);

        $this->amqpConnection->shouldReceive('close');

        $this->amqpMessage = Mockery::mock(AMQPMessage::class);
        $this->amqpMessage->delivery_info['delivery_tag'] = uniqid('delivery_tag', true);

        $this->listenerProvider = Mockery::mock(ListenerProvider::class);

        $this->transport = new AmqpTransport($this->connectionSettings, $this->serializer, $this->listenerProvider);
    }

    public function tearDown(): void
    {
        $this->transport->__destruct();
        Mockery::close();
    }

    public function testPublish(): void
    {
        $serializedMessageData = uniqid('data', true);

        $this->queueDeclareMessage();

        $this->serializer->shouldReceive('serialize')
            ->with($this->message)
            ->once()
            ->andReturn($serializedMessageData);

        $this->channel->shouldReceive('basic_publish')
            ->with(
                Mockery::type(AMQPMessage::class),
                $this->exchange,
                AmqpTransport::MESSAGE_QUEUE_PREFIX . $this->routingKey
            )
            ->once();

        $this->transport->publish($this->message);

        //For coverage
        $this->assertTrue(true);
    }

    public function testPoll(): void
    {
        $this->channel->shouldReceive('wait')
            ->once();

        $this->transport->poll();

        //For coverage
        $this->assertTrue(true);
    }

    public function testSubscribe(): void
    {
        $this->queueDeclareMessage();

        $this->channel->shouldReceive('basic_qos')
            ->with(0, 1, false)
            ->once();

        $this->channel->shouldReceive('basic_consume')
            ->with(
                AmqpTransport::MESSAGE_QUEUE_PREFIX . $this->routingKey,
                '',
                false,
                false,
                false,
                false,
                Mockery::type(Closure::class)
            );

        $handler = function (Message $e) {
        };

        $result = $this->transport->subscribe($this->subscriptionSettings, $handler);

        $this->assertInstanceOf(AmqpTransport::class, $result);
    }

    public function testCanHandleMessage(): void
    {
        $messageBody = uniqid('messageBody', true);

        $handler = function (Message $e) {
            return;
        };
        $callback = $this->getSubscriptionCallback($handler);

        $this->amqpMessage->shouldReceive('getBody')
            ->once()
            ->andReturn($messageBody);

        $this->serializer->shouldReceive('deserialize')
            ->with($messageBody)
            ->once()
            ->andReturn($this->message);

        $this->channel->shouldReceive('basic_ack')
            ->with($this->amqpMessage->delivery_info['delivery_tag'])
            ->once();

        $this->listenerProvider->shouldReceive('eventHasListeners')
            ->once()
            ->andReturnTrue();

        $callback($this->amqpMessage);

        $this->assertTrue(true);
    }

    public function testCanFailedMessageBecauseDeserializationFailed(): void
    {
        $messageBody = uniqid('messageBody', true);

        $handler = function (Message $e) {
            return;
        };
        $callback = $this->getSubscriptionCallback($handler);

        $this->amqpMessage->shouldReceive('getBody')
            ->once()
            ->andReturn($messageBody);

        $this->serializer->shouldReceive('deserialize')
            ->with($messageBody)
            ->once()
            ->andThrows(new DeserializationFailedException());

        $this->queueDeclareFailure();

        $this->channel->shouldReceive('basic_publish')
            ->with($this->amqpMessage, '', AmqpTransport::FAILURE_QUEUE);

        $this->channel->shouldReceive('basic_ack')
            ->with($this->amqpMessage->delivery_info['delivery_tag'])
            ->once();


        $callback($this->amqpMessage);

        $this->assertTrue(true);
    }

    public function testCanFailedMessageBecauseNotInstanceOfMessage(): void
    {
        $messageBody = uniqid('messageBody', true);

        $handler = function (Message $e) {
            return;
        };
        $callback = $this->getSubscriptionCallback($handler);

        $this->amqpMessage->shouldReceive('getBody')
            ->once()
            ->andReturn($messageBody);

        $this->serializer->shouldReceive('deserialize')
            ->with($messageBody)
            ->once()
            ->andReturn(new \stdClass());

        $this->queueDeclareFailure();

        $this->channel->shouldReceive('basic_publish')
            ->with($this->amqpMessage, '', AmqpTransport::FAILURE_QUEUE);

        $this->channel->shouldReceive('basic_ack')
            ->with($this->amqpMessage->delivery_info['delivery_tag'])
            ->once();


        $callback($this->amqpMessage);

        $this->assertTrue(true);
    }

    public function testCanDelayMessage(): void
    {
        $messageBody = uniqid('messageBody', true);

        $handler = function (Message $e) {
            throw new \Exception();
        };
        $callback = $this->getSubscriptionCallback($handler);

        $this->amqpMessage->shouldReceive('getBody')
            ->once()
            ->andReturn($messageBody);

        $this->serializer->shouldReceive('deserialize')
            ->with($messageBody)
            ->once()
            ->andReturn($this->message);

        $this->message->shouldReceive('setLastError');
        $this->message->shouldReceive('isDeadLetter')->once()->andReturn(false);

        $this->queueDeclareDelay();
        $this->serializer->shouldReceive('serialize')->with($this->message)->andReturn($messageBody);
        $this->amqpMessage->shouldReceive('setBody')->with($messageBody);

        $this->channel->shouldReceive('basic_ack')
            ->with($this->amqpMessage->delivery_info['delivery_tag'])
            ->once();

        $this->listenerProvider->shouldReceive('eventHasListeners')
            ->once()
            ->andReturnTrue();

        $this->channel->shouldReceive('basic_publish')
            ->with($this->amqpMessage, '', AmqpTransport::DELAY_QUEUE_PREFIX . $this->routingKey);

        $callback($this->amqpMessage);

        $this->assertTrue(true);
    }

    public function testCanDeadLetterMessage(): void
    {
        $messageBody = uniqid('messageBody', true);

        $handler = function (Message $e) {
            throw new \Exception('error message in exception');
        };
        $callback = $this->getSubscriptionCallback($handler);

        $this->amqpMessage->shouldReceive('getBody')
            ->once()
            ->andReturn($messageBody);

        $this->message->shouldReceive('setLastError')
            ->with('error message in exception');

        $this->serializer->shouldReceive('deserialize')
            ->with($messageBody)
            ->once()
            ->andReturn($this->message);

        $this->message->shouldReceive('isDeadLetter')->once()->andReturn(true);

        $this->queueDeclareDeadLetter();
        $this->serializer->shouldReceive('serialize')->with($this->message)->andReturn($messageBody);
        $this->amqpMessage->shouldReceive('setBody')->with($messageBody);

        $this->channel->shouldReceive('basic_publish')
            ->with($this->amqpMessage, '', AmqpTransport::DEAD_LETTER_QUEUE_PREFIX . $this->routingKey);

        $this->channel->shouldReceive('basic_ack')
            ->with($this->amqpMessage->delivery_info['delivery_tag'])
            ->once();

        $this->listenerProvider->shouldReceive('eventHasListeners')
            ->once()
            ->andReturnTrue();

        $callback($this->amqpMessage);

        $this->assertTrue(true);
    }

    /**
     * @param Closure $handler
     * @return Closure
     * @throws \ReflectionException
     */
    private function getSubscriptionCallback(Closure $handler): Closure
    {
        $reflection = new ReflectionClass(AmqpTransport::class);

        $method = $reflection->getMethod('createCallbackFromHandler');
        $method->setAccessible(true);

        return $method->getClosure($this->transport)($handler);
    }

    private function queueDeclareMessage(): void
    {
        $this->declareQueue(AmqpTransport::MESSAGE_QUEUE_PREFIX . $this->routingKey, null);
    }

    private function queueDeclareDelay(): void
    {
        $this->declareQueue(
            AmqpTransport::DELAY_QUEUE_PREFIX . $this->routingKey,
            Mockery::type(AMQPTable::class)
        );
    }

    private function queueDeclareDeadLetter(): void
    {
        $this->declareQueue(AmqpTransport::DEAD_LETTER_QUEUE_PREFIX . $this->routingKey, null);
    }

    private function queueDeclareFailure(): void
    {
        $this->declareQueue(AmqpTransport::FAILURE_QUEUE, null);
    }

    private function declareQueue(string $name, $args): void
    {
        $this->channel->shouldReceive('queue_declare')
            ->with(
                $name,
                false,
                true,
                false,
                false,
                false,
                $args
            )
            ->once();
    }
}
