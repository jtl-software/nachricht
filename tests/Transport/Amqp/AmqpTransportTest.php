<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Transport\Amqp;

use Closure;
use JTL\Nachricht\Collection\StringCollection;
use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Contract\Serializer\EventSerializer;
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
     * @var EventSerializer|Mockery\MockInterface
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
     * @var Event|Mockery\MockInterface
     */
    private $event;

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
    private $message;

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

        $this->serializer = Mockery::mock(EventSerializer::class);
        $this->channel = Mockery::mock(AMQPChannel::class);
        $this->amqpConnection = Mockery::mock('overload:' . AMQPStreamConnection::class, [
            'channel' => $this->channel
        ]);
        $this->event = Mockery::mock(Event::class, [
            'getRoutingKey' => $this->routingKey,
            'getExchange' => $this->exchange,
            'getMaxRetryCount' => 3
        ]);

        $this->subscriptionSettings = Mockery::mock(SubscriptionSettings::class, [
            'getQueueNameList' => $this->queueNameList
        ]);

        $this->amqpConnection->shouldReceive('close');

        $this->message = Mockery::mock(AMQPMessage::class);
        $this->message->delivery_info['delivery_tag'] = uniqid('delivery_tag', true);

        $this->transport = new AmqpTransport($this->connectionSettings, $this->serializer);
    }

    public function tearDown(): void
    {
        $this->transport->__destruct();
        Mockery::close();
    }

    public function testPublish(): void
    {
        $serializedEventData = uniqid('data', true);

        $this->queueDeclareMessage();

        $this->serializer->shouldReceive('serialize')
            ->with($this->event)
            ->once()
            ->andReturn($serializedEventData);

        $this->channel->shouldReceive('basic_publish')
            ->with(
                Mockery::type(AMQPMessage::class),
                $this->exchange,
                AmqpTransport::MESSAGE_QUEUE_PREFIX . $this->routingKey
            )
            ->once();

        $this->transport->publish($this->event);

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

        $handler = function (Event $e) {
        };

        $result = $this->transport->subscribe($this->subscriptionSettings, $handler);

        $this->assertInstanceOf(AmqpTransport::class, $result);
    }

    public function testCanHandleMessage(): void
    {
        $messageBody = uniqid('messageBody', true);

        $handler = function (Event $e) {
            return;
        };
        $callback = $this->getSubscriptionCallback($handler);

        $this->message->shouldReceive('getBody')
            ->once()
            ->andReturn($messageBody);

        $this->serializer->shouldReceive('deserialize')
            ->with($messageBody)
            ->once()
            ->andReturn($this->event);

        $this->channel->shouldReceive('basic_ack')
            ->with($this->message->delivery_info['delivery_tag'])
            ->once();


        $callback($this->message);

        $this->assertTrue(true);
    }

    public function testCanFailedMessageBecauseDeserializationFailed(): void
    {
        $messageBody = uniqid('messageBody', true);

        $handler = function (Event $e) {
            return;
        };
        $callback = $this->getSubscriptionCallback($handler);

        $this->message->shouldReceive('getBody')
            ->once()
            ->andReturn($messageBody);

        $this->serializer->shouldReceive('deserialize')
            ->with($messageBody)
            ->once()
            ->andThrows(new DeserializationFailedException());

        $this->queueDeclareFailure();

        $this->channel->shouldReceive('basic_publish')
            ->with($this->message, '', AmqpTransport::FAILURE_QUEUE);

        $this->channel->shouldReceive('basic_ack')
            ->with($this->message->delivery_info['delivery_tag'])
            ->once();


        $callback($this->message);

        $this->assertTrue(true);
    }

    public function testCanFailedMessageBecauseNotInstanceOfEvent(): void
    {
        $messageBody = uniqid('messageBody', true);

        $handler = function (Event $e) {
            return;
        };
        $callback = $this->getSubscriptionCallback($handler);

        $this->message->shouldReceive('getBody')
            ->once()
            ->andReturn($messageBody);

        $this->serializer->shouldReceive('deserialize')
            ->with($messageBody)
            ->once()
            ->andReturn(new \stdClass());

        $this->queueDeclareFailure();

        $this->channel->shouldReceive('basic_publish')
            ->with($this->message, '', AmqpTransport::FAILURE_QUEUE);

        $this->channel->shouldReceive('basic_ack')
            ->with($this->message->delivery_info['delivery_tag'])
            ->once();


        $callback($this->message);

        $this->assertTrue(true);
    }

    public function testCanDelayMessage(): void
    {
        $messageBody = uniqid('messageBody', true);

        $handler = function (Event $e) {
            throw new \Exception();
        };
        $callback = $this->getSubscriptionCallback($handler);

        $this->message->shouldReceive('getBody')
            ->once()
            ->andReturn($messageBody);

        $this->serializer->shouldReceive('deserialize')
            ->with($messageBody)
            ->once()
            ->andReturn($this->event);

        $this->message->shouldReceive('get_properties')
            ->once()
            ->andReturn([]);

        $this->queueDeclareDelay();

        $this->channel->shouldReceive('basic_ack')
            ->with($this->message->delivery_info['delivery_tag'])
            ->once();

        $this->channel->shouldReceive('basic_publish')
            ->with($this->message, '', AmqpTransport::DELAY_QUEUE_PREFIX . $this->routingKey);

        $callback($this->message);

        $this->assertTrue(true);
    }

    public function testCanDelayMessageWithoutDeathHeader(): void
    {
        $messageBody = uniqid('messageBody', true);

        $handler = function (Event $e) {
            throw new \Exception();
        };
        $callback = $this->getSubscriptionCallback($handler);

        $this->message->shouldReceive('getBody')
            ->once()
            ->andReturn($messageBody);

        $this->serializer->shouldReceive('deserialize')
            ->with($messageBody)
            ->once()
            ->andReturn($this->event);


        $headers = new AMQPTable([
            'x-death' => [
            ]
        ]);

        $this->message->shouldReceive('get_properties')
            ->twice()
            ->andReturn([
                'application_headers' => $headers
            ]);


        $this->queueDeclareDelay();

        $this->channel->shouldReceive('basic_ack')
            ->with($this->message->delivery_info['delivery_tag'])
            ->once();

        $this->channel->shouldReceive('basic_publish')
            ->with($this->message, '', AmqpTransport::DELAY_QUEUE_PREFIX . $this->routingKey);

        $callback($this->message);

        $this->assertTrue(true);
    }

    public function testCanDeadLetterMessage(): void
    {
        $messageBody = uniqid('messageBody', true);

        $handler = function (Event $e) {
            throw new \Exception();
        };
        $callback = $this->getSubscriptionCallback($handler);

        $this->message->shouldReceive('getBody')
            ->once()
            ->andReturn($messageBody);

        $this->serializer->shouldReceive('deserialize')
            ->with($messageBody)
            ->once()
            ->andReturn($this->event);

        $this->queueDeclareDeadLetter();

        $headers = new AMQPTable([
            'x-death' => [
                [
                    'count' => 6
                ]
            ]
        ]);

        $this->message->shouldReceive('get_properties')
            ->twice()
            ->andReturn([
                'application_headers' => $headers
            ]);

        $this->channel->shouldReceive('basic_ack')
            ->with($this->message->delivery_info['delivery_tag'])
            ->once();

        $this->channel->shouldReceive('basic_publish')
            ->with($this->message, '', AmqpTransport::DEAD_LETTER_QUEUE_PREFIX . $this->routingKey);

        $callback($this->message);

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
