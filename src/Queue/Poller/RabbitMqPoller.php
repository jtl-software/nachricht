<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 21/05/19
 */

namespace JTL\Nachricht\Queue\Poller;


use JTL\Nachricht\Contracts\Event\Event;
use JTL\Nachricht\Dispatcher\RabbitMqDispatcher;
use JTL\Nachricht\Queue\Client\ConnectionSettings;
use JTL\Nachricht\Queue\Client\RabbitMqClient;
use JTL\Nachricht\Queue\Client\SubscriptionSettings;

class RabbitMqPoller
{
    /**
     * @var RabbitMqClient
     */
    private $client;

    /**
     * @var RabbitMqDispatcher
     */
    private $dispatcher;

    public function __construct(RabbitMqClient $client, RabbitMqDispatcher $dispatcher)
    {
        $this->client = $client;
        $this->dispatcher = $dispatcher;
    }

    public function run(ConnectionSettings $connectionSettings, SubscriptionSettings $subscriptionSettings): void
    {
        $this->client->connect($connectionSettings)
            ->subscribe($subscriptionSettings, function (Event $event) {
                return $this->dispatcher->dispatch($event);
            });

        while (true) {
            $this->client->poll();
        }
    }
}
