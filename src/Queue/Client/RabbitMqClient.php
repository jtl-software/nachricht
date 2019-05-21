<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/17
 */

namespace JTL\Nachricht\Queue\Client;


use Closure;
use JTL\Nachricht\Contracts\Event\AmqpEvent;
use JTL\Nachricht\Contracts\Event\Event;
use JTL\Nachricht\Contracts\Queue\Client\MessageClient;
use JTL\Nachricht\Contracts\Serializer\EventSerializer;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMqClient implements MessageClient
{
    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var EventSerializer
     */
    private $serializer;


    public function __construct(EventSerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    public function connect(ConnectionSettings $connectionSettings): MessageClient
    {
        $this->connection = new AMQPStreamConnection(
            $connectionSettings->getHost(),
            $connectionSettings->getPort(),
            $connectionSettings->getUser(),
            $connectionSettings->getPassword()
        );
        return $this;
    }

    /**
     * @param AmqpEvent|Event $event
     */
    public function publish(Event $event): void
    {
        $amqpMessage = new AMQPMessage($this->serializer->serialize($event));
        $this->connection->channel()->basic_publish($amqpMessage, $event->getExchange(), $event->getRoutingKey());
    }

    public function subscribe(array $subscriptionOptions, Closure $handler): MessageClient
    {
        $channel = $this->connection->channel();
        //$channel->queue_declare($subscriptionOptions['queueName'], false, true, false, false);
        $channel->basic_consume(
            $subscriptionOptions['queueName'],
            '',
            false,
            false,
            false,
            false,
            static function (AMQPMessage $data) {
                var_dump($data->getBody());
                //$handler($event);
            }
        );

        return $this;
    }

    public function poll(): void
    {
        $this->connection->channel()->wait();
    }

    /**
     * @return Closure
     */
    private function createCallbackFromDispatcher(): Closure
    {
        return function(AMQPMessage $message) {
            $event = $this->serializer->deserialize($message->getBody());
            $this->dispatcher->dispatch($event);
        };
    }
}
