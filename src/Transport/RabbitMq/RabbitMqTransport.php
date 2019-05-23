<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/17
 */

namespace JTL\Nachricht\Transport\RabbitMq;

use Closure;
use Exception;
use JTL\Nachricht\Contracts\Event\Event;
use JTL\Nachricht\Contracts\Serializer\EventSerializer;
use JTL\Nachricht\Contracts\Transport\EventTransport;
use JTL\Nachricht\Transport\SubscriptionSettings;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Throwable;

class RabbitMqTransport implements EventTransport
{
    private const MESSAGE_QUEUE_PREFIX = 'msg__';
    private const DELAY_QUEUE_PREFIX = 'delayed__';
    private const DEAD_LETTER_QUEUE_PREFIX = 'dl__';

    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var EventSerializer
     */
    private $serializer;

    /**
     * @var AMQPChannel
     */
    private $channel;
    
    /**
     * @var array
     */
    private $declaredQueueList;

    /**
     * RabbitMqTransport constructor.
     * @param RabbitMqConnectionSettings $connectionSettings
     * @param EventSerializer $serializer
     */
    public function __construct(
        RabbitMqConnectionSettings $connectionSettings,
        EventSerializer $serializer
    ) {
        $this->serializer = $serializer;

        $this->connection = new AMQPStreamConnection(
            $connectionSettings->getHost(),
            $connectionSettings->getPort(),
            $connectionSettings->getUser(),
            $connectionSettings->getPassword(),
            $connectionSettings->getVhost()
        );

        $this->channel = $this->connection->channel();
        $this->declaredQueueList = [];
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }

    /**
     * @param Event $event
     */
    public function publish(Event $event): void
    {
        $this->declareQueue(self::MESSAGE_QUEUE_PREFIX . $event->getRoutingKey());
        $amqpMessage = new AMQPMessage($this->serializer->serialize($event));
        $this->channel->basic_publish(
            $amqpMessage,
            $event->getExchange(),
            self::MESSAGE_QUEUE_PREFIX . $event->getRoutingKey()
        );
    }

    /**
     * @param SubscriptionSettings $subscriptionOptions
     * @param Closure $handler
     * @return EventTransport
     */
    public function subscribe(SubscriptionSettings $subscriptionOptions, Closure $handler): EventTransport
    {
        foreach ($subscriptionOptions->getQueueNameList() as $queue) {
            $this->channel->queue_declare($queue, false, true, false, false);
            $this->channel->basic_qos(0, 1, false);
            $this->channel->basic_consume(
                $queue,
                '',
                false,
                false,
                false,
                false,
                $this->createCallbackFromHandler($handler)
            );
        }

        return $this;
    }

    public function poll(): void
    {
        $this->channel->wait();
    }

    /**
     * @param Closure $handler
     * @return Closure
     */
    private function createCallbackFromHandler(Closure $handler): Closure
    {
        $serializer = $this->serializer;
        return function (AMQPMessage $message) use ($serializer, $handler) {
            /** @var AMQPChannel $channel */
            $event = $serializer->deserialize($message->getBody());

            try {
                $result = $handler($event);
            } catch (Exception|Throwable $exception) {
                $this->handleFailedEvent($message, $event);
                return;
            }

            if ($result) {
                $this->ack($message);
            } else {
                $this->nack($message);
            }
        };
    }

    /**
     * @param AMQPMessage $message
     * @param Event $event
     */
    private function handleFailedEvent(AMQPMessage $message, Event $event): void
    {
        $this->ack($message);
        if ($this->maxRetryCountReached($message, $event)) {
            $this->deadLetterMessage($message, $event);
            return;
        }

        $this->delayMessage($message, $event);
    }

    /**
     * @param AMQPMessage $message
     */
    private function ack(AMQPMessage $message): void
    {
        $this->channel->basic_ack($message->delivery_info['delivery_tag']);
    }

    /**
     * @param AMQPMessage $message
     */
    private function nack(AMQPMessage $message): void
    {
        $this->channel->basic_nack($message->delivery_info['delivery_tag']);
    }

    /**
     * @param AMQPMessage $message
     * @param Event $event
     * @return bool
     */
    private function maxRetryCountReached(AMQPMessage $message, Event $event): bool
    {
        if (!isset($message->get_properties()['application_headers'])) {
            return false;
        }

        /** @var AMQPTable $headers */
        $headers = $message->get_properties()['application_headers'];
        $headerData = $headers->getNativeData();

        if (!isset($headerData['x-death'][0]['count'])) {
            return false;
        }

        return $headerData['x-death'][0]['count'] >= $event->getMaxRetryCount();
    }

    /**
     * @param AMQPMessage $message
     * @param Event $event
     */
    private function delayMessage(AMQPMessage $message, Event $event): void
    {
        $delayQueueName = $this->declareDelayQueue($event->getRoutingKey());
        $this->channel->basic_publish($message, '', $delayQueueName);
    }

    /**
     * @param AMQPMessage $message
     * @param Event $event
     */
    private function deadLetterMessage(AMQPMessage $message, Event $event): void
    {
        $deadLetterQueueName = $this->declareDeadLetterQueue($event->getRoutingKey());
        $this->channel->basic_publish($message, '', $deadLetterQueueName);
    }

    /**
     * @param string $queueName
     * @param int $delay
     * @return string
     */
    private function declareDelayQueue(string $queueName, int $delay = 1000): string
    {
        $arguments = new AMQPTable(
            [
                'x-message-ttl' => $delay,
                'x-dead-letter-exchange' => '',
                'x-dead-letter-routing-key' => self::MESSAGE_QUEUE_PREFIX . $queueName
            ]
        );

        $delayQueueName = self::DELAY_QUEUE_PREFIX . $queueName;

        $this->declareQueue($delayQueueName, $arguments);
        return $delayQueueName;
    }

    /**
     * @param string $queueName
     * @return string
     */
    private function declareDeadLetterQueue(string $queueName): string
    {
        $deadLetterQueueName = self::DEAD_LETTER_QUEUE_PREFIX . $queueName;
        $this->declareQueue($deadLetterQueueName);

        return $deadLetterQueueName;
    }

    /**
     * @param string $queueName
     * @return bool
     */
    private function queueAlreadyDeclared(string $queueName): bool
    {
        return in_array($queueName, $this->declaredQueueList);
    }

    /**
     * @param string $queueName
     * @param AMQPTable|null $arguments
     */
    private function declareQueue(string $queueName, AMQPTable $arguments = null): void
    {
        if (!$this->queueAlreadyDeclared($queueName)) {
            echo 'Declare ' . $queueName . "\n";
            $this->channel->queue_declare(
                $queueName,
                false,
                true,
                false,
                false,
                false,
                $arguments
            );
            $this->declaredQueueList[] = $queueName;
        }
    }
}
