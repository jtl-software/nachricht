<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/17
 */

namespace JTL\Nachricht\Transport\Amqp;

use Closure;
use Exception;
use JTL\Nachricht\Contract\Message\AmqpTransportableMessage;
use JTL\Nachricht\Contract\Serializer\MessageSerializer;
use JTL\Nachricht\Listener\ListenerProvider;
use JTL\Nachricht\Log\EchoLogger;
use JTL\Nachricht\Serializer\Exception\DeserializationFailedException;
use JTL\Nachricht\Transport\SubscriptionSettings;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerInterface;
use Throwable;

class AmqpTransport
{
    public const MESSAGE_QUEUE_PREFIX = 'msg__';
    public const DEAD_LETTER_QUEUE_PREFIX = 'dl__';
    public const MISSING_LISTENER_QUEUE_PREFIX = 'missing_listener__';
    public const FAILURE_QUEUE = 'failure';

    /**
     * @var array<string, bool>
     */
    private array $declaredQueueList = [];

    private AmqpConnectionSettings $connectionSettings;
    private MessageSerializer $serializer;

    private ListenerProvider $listenerProvider;

    private LoggerInterface $logger;

    private ?AMQPStreamConnection $connection = null;
    private AMQPChannel $channel;

    /**
     * @var array<string>
     */
    private array $consumers = [];

    /**
     * AmqpTransport constructor.
     *
     * @param AmqpConnectionSettings $connectionSettings
     * @param MessageSerializer $serializer
     * @param ListenerProvider $listenerProvider
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        AmqpConnectionSettings $connectionSettings,
        MessageSerializer $serializer,
        ListenerProvider $listenerProvider,
        LoggerInterface $logger = null
    ) {
        $this->connectionSettings = $connectionSettings;
        $this->serializer = $serializer;
        $this->listenerProvider = $listenerProvider;
        if ($logger === null) {
            $this->logger = new EchoLogger();
        } else {
            $this->logger = $logger;
        }
    }

    public function __destruct()
    {
        if ($this->connection instanceof AMQPStreamConnection) {
            $this->connection->close();
        }
    }

    public function publish(AmqpTransportableMessage $message, int $delay = 0): void
    {
        $this->connect();
        $routingKey = self::MESSAGE_QUEUE_PREFIX . $message->getRoutingKey();
        $exchange = '';
        $properties = ['delivery_mode' => 2];

        $this->prepareExchanges();
        $this->declareQueue($routingKey);

        if ($delay > 0) {
            $exchange = 'delayed_exchange';
            $properties['application_headers'] = new AMQPTable([
                'x-delay' => $delay * 1000,
            ]);
        }

        $this->channel->basic_publish(
            new AMQPMessage(
                $this->serializer->serialize($message),
                $properties
            ),
            $exchange,
            $routingKey
        );
    }

    public function directPublish(AMQPMessage $message): void
    {
        $this->connect();
        $routingKey = self::MESSAGE_QUEUE_PREFIX . $message->getRoutingKey();
        $this->declareQueue($routingKey);
        $this->channel->basic_publish(
            msg: $message,
            routing_key: $routingKey
        );
    }


    /**
     * @param SubscriptionSettings $subscriptionOptions
     * @param Closure $handler
     * @return AmqpTransport
     */
    public function subscribe(SubscriptionSettings $subscriptionOptions, Closure $handler): AmqpTransport
    {
        $this->connect();
        $this->prepareExchanges();

        foreach ($subscriptionOptions->getQueueNameList() as $queue) {
            $this->declareQueue($queue);
            $this->channel->basic_qos(0, 1, false);
            $consumerTag = $this->channel->basic_consume(
                $queue,
                '',
                false,
                false,
                false,
                false,
                $this->createCallbackFromHandler($handler)
            );

            $this->consumers[] = $consumerTag;
            $this->channel->queue_bind($queue, 'delayed_exchange', $queue);
        }

        return $this;
    }

    public function renewSubscription(SubscriptionSettings $subscriptionSettings, Closure $handler): AmqpTransport
    {
        foreach ($this->consumers as $consumer) {
            $this->channel->basic_cancel($consumer);
        }
        $this->consumers = [];
        return $this->subscribe($subscriptionSettings, $handler);
    }

    public function poll(int $timout): void
    {
        $this->connect();
        $this->channel->wait(null, false, $timout);
    }

    public function getMessageFromQueue(string $queue, bool $noAck): ?AMQPMessage
    {
        return $this->channel->basic_get($queue, $noAck);
    }

    public function getConnectionSettings(): AmqpConnectionSettings
    {
        return $this->connectionSettings;
    }

    public function ack(AMQPMessage $message): void
    {
        $this->channel->basic_ack($message->getDeliveryTag());
    }

    public function countMessagesInQueue(string $queue): int
    {
        $result = $this->channel->queue_declare($queue, false, true, false, false);
        return (int)($result[1] ?? 0);
    }

    public function connect(): void
    {
        if ($this->connection === null) {
            $this->connection = new AMQPStreamConnection(
                $this->connectionSettings->getHost(),
                $this->connectionSettings->getPort(),
                $this->connectionSettings->getUser(),
                $this->connectionSettings->getPassword(),
                $this->connectionSettings->getVhost()
            );

            $this->channel = $this->connection->channel();
        }
    }

    /**
     * @param string $queueName
     * @param AMQPTable<mixed>|null $arguments
     */
    private function declareQueue(string $queueName, AMQPTable $arguments = null): void
    {
        if (!$this->queueAlreadyDeclared($queueName)) {
            $this->channel->queue_declare(
                $queueName,
                false,
                true,
                false,
                false,
                false,
                /** @phpstan-ignore-next-line */
                $arguments
            );
            $this->declaredQueueList[$queueName] = true;
        }
    }

    private function prepareExchanges(): void
    {
        $this->connect();

        $this->channel->exchange_declare(
            exchange: 'delayed_exchange',
            type: 'x-delayed-message',
            durable: true,
            auto_delete: false,
            arguments: new AMQPTable([
                'x-delayed-type' => 'direct',
            ])
        );
    }

    /**
     * @param string $queueName
     * @return bool
     */
    private function queueAlreadyDeclared(string $queueName): bool
    {
        return isset($this->declaredQueueList[$queueName]);
    }

    /**
     * @param Closure $handler
     * @return Closure
     */
    private function createCallbackFromHandler(Closure $handler): Closure
    {
        $this->connect();
        $serializer = $this->serializer;
        return function (AMQPMessage $message) use ($serializer, $handler) {
            try {
                $event = $serializer->deserialize($message->getBody());
                if (!$event instanceof AmqpTransportableMessage) {
                    $this->logError(
                        sprintf(
                            'Event class "%s"  need to implement %s',
                            get_class($event),
                            AmqpTransportableMessage::class
                        )
                    );
                    $this->handleUnprocessableMessage($message);
                } else {
                    if ($this->listenerProvider->eventHasListeners($event)) {
                        try {
                            $handler($event);
                            $this->logger->debug('Handled message of type ' . get_class($event) . "successful");
                        } catch (Exception|Throwable $exception) {
                            $this->logError($exception->__toString());
                            $this->handleFailedMessage($message, $event, $exception);
                        }
                    } else {
                        $this->logError(sprintf('No Listener found for "%s" event', get_class($event)));
                        $this->handleMessageWithoutListener($message, $event);
                    }
                }
            } catch (DeserializationFailedException $exception) {
                $this->logError($exception->__toString());
                $this->handleUnprocessableMessage($message);
            }

            $this->ack($message);
        };
    }

    private function logError(string $message): void
    {
        $this->logger->error($message);
    }

    /**
     * @param AMQPMessage $message
     */
    private function handleUnprocessableMessage(AMQPMessage $message): void
    {
        $this->publishMessageToFailureQueue($message);
    }

    /**
     * @param AMQPMessage $amqpMessage
     * @param AmqpTransportableMessage $message
     */
    private function handleMessageWithoutListener(AMQPMessage $amqpMessage, AmqpTransportableMessage $message): void
    {
        $queueName = self::MISSING_LISTENER_QUEUE_PREFIX . $message->getRoutingKey();
        $this->declareQueue($queueName);
        $this->channel->basic_publish($amqpMessage, '', $queueName);
    }

    /**
     * @param AMQPMessage $message
     */
    private function publishMessageToFailureQueue(AMQPMessage $message): void
    {
        $this->declareQueue(self::FAILURE_QUEUE);
        $this->channel->basic_publish($message, '', self::FAILURE_QUEUE);
    }

    /**
     * @param AMQPMessage $amqpMessage
     * @param AmqpTransportableMessage $message
     * @param Throwable $throwable
     */
    private function handleFailedMessage(
        AMQPMessage $amqpMessage,
        AmqpTransportableMessage $message,
        Throwable $throwable
    ): void {
        $message->setLastError($throwable->getMessage());
        if ($message->isDeadLetter()) {
            $this->publishMessageToDeadLetterQueue($amqpMessage, $message);
        } else {
            $this->publish($message, $message->getRetryDelay());
        }
    }

    /**
     * @param AMQPMessage $amqpMessage
     * @param AmqpTransportableMessage $message
     */
    private function publishMessageToDeadLetterQueue(AMQPMessage $amqpMessage, AmqpTransportableMessage $message): void
    {
        $deadLetterQueueName = $this->declareDeadLetterQueue($message->getRoutingKey());
        $amqpMessage->setBody($this->serializer->serialize($message));
        $this->channel->basic_publish($amqpMessage, '', $deadLetterQueueName);

        $this->logger->info('Pushed deadletter of type '.get_class($amqpMessage). " to {$deadLetterQueueName}");
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
}
