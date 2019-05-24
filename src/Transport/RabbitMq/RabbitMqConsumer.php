<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 21/05/19
 */

namespace JTL\Nachricht\Transport\RabbitMq;

use JTL\Nachricht\Contracts\Event\Event;
use JTL\Nachricht\Contracts\Transport\Consumer;
use JTL\Nachricht\Dispatcher\RabbitMqDispatcher;
use JTL\Nachricht\Serializer\Exception\DeserializationFailedException;
use JTL\Nachricht\Transport\SubscriptionSettings;

class RabbitMqConsumer implements Consumer
{
    /**
     * @var RabbitMqTransport
     */
    private $transport;

    /**
     * @var RabbitMqDispatcher
     */
    private $dispatcher;

    /**
     * RabbitMqConsumer constructor.
     * @param RabbitMqTransport $client
     * @param RabbitMqDispatcher $dispatcher
     */
    public function __construct(RabbitMqTransport $client, RabbitMqDispatcher $dispatcher)
    {
        $this->transport = $client;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param SubscriptionSettings $subscriptionSettings
     * @throws DeserializationFailedException
     */
    public function consume(SubscriptionSettings $subscriptionSettings): void
    {
        $this->transport->subscribe($subscriptionSettings, function (Event $event) {
            return $this->dispatcher->dispatch($event);
        });

        while (true) {
            $this->transport->poll();
        }
    }
}
