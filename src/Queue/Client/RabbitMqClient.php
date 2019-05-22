<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/17
 */

namespace JTL\Nachricht\Queue\Client;


use Closure;
use JTL\Nachricht\Contracts\Event\Event;
use JTL\Nachricht\Contracts\Queue\Client\MessageClient;
use JTL\Nachricht\Contracts\Serializer\EventSerializer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

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

    /**
     * @var AMQPChannel
     */
    private $channel;


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
        $this->channel = $this->connection->channel();
        return $this;
    }

    /**
     * @param Event $event
     */
    public function publish(Event $event): void
    {
        $amqpMessage = new AMQPMessage($this->serializer->serialize($event));
        $this->channel->basic_publish($amqpMessage, $event->getExchange(), $event->getRoutingKey());
    }

    public function subscribe(SubscriptionSettings $subscriptionOptions, Closure $handler): MessageClient
    {
        //$this->channel->queue_declare('default_delay_1000', false, true, false, false);

        foreach ($subscriptionOptions->getQueueNameList() as $queue) {
            $this->channel->queue_declare($queue, false, true, false, false);
            $this->channel->basic_consume(
                $queue,
                '',
                false,
                false,
                false,
                false,
                $this->createCallbackFromDispatcher($handler)
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
    private function createCallbackFromDispatcher(Closure $handler): Closure
    {
        $serializer = $this->serializer;
        return static function(AMQPMessage $data) use ($serializer, $handler) {
            /** @var AMQPChannel $channel */
            $channel = $data->delivery_info['channel'];
            $event = $serializer->deserialize($data->getBody());

            try {
                $result = $handler($event);
            } catch (\Exception|\Throwable $e) {
                $channel->basic_ack($data->delivery_info['delivery_tag']);
                $queueName = $data->get('routing_key');
                $channel->queue_declare(
                    'delayed_' . $queueName,
                    false,
                    true,
                    false,
                    false,
                    false,
                    new AMQPTable([
                        'x-message-ttl' => 1000,
                        'x-dead-letter-exchange' => '',
                        'x-dead-letter-routing-key' => $queueName
                    ])
                );

                $properties = $data->get_properties();

                if (isset($properties['application_headers'])) {
                    /** @var AMQPTable $headers */
                    $headers = $properties['application_headers'];
                    $headerData = $headers->getNativeData();

                    if (isset($headerData['x-death'])) {
                        $xDeath = $headerData['x-death'];

                        if (count($xDeath) === 1 && isset($xDeath[0]['count'])) {
                            $retryCount = $xDeath[0]['count'];

                            if ($retryCount >= $event->getMaxRetryCount()) {
                                echo "There was an exception but max retry count was reached\n";
                                echo "--- done ---\n\n";
                                return;
                            }
                        }
                    }
                }

                $channel->basic_publish($data, '', 'delayed_' . $queueName);

                echo "There was an exception\n";
                echo "--- done ---\n\n";
                return;
            }

            if ($result === true) {
                $channel->basic_ack($data->delivery_info['delivery_tag']);
                echo "Everything OK\n";
            } else {
                $channel->basic_nack($data->delivery_info['delivery_tag']);
                echo "Task failed successfully\n";
            }

            echo "--- done ---\n\n";
        };
    }
}
