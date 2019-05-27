<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 21/05/19
 */

namespace JTL\Nachricht\Transport\RabbitMq;

use Closure;
use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Contract\Transport\Consumer;
use JTL\Nachricht\Dispatcher\RabbitMqDispatcher;
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
     */
    public function consume(SubscriptionSettings $subscriptionSettings): void
    {
        $this->transport->subscribe($subscriptionSettings, $this->createCallback());

        while (true) {
            $this->transport->poll();
        }
    }

    /**
     * @return Closure
     */
    private function createCallback(): Closure
    {
        return function (Event $event) {
            $this->dispatcher->dispatch($event);
        };
    }
}
