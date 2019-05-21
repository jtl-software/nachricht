<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 21/05/19
 */

namespace JTL\Nachricht\Emitter;


use JTL\Nachricht\Contracts\Emitter\Emitter;
use JTL\Nachricht\Contracts\Event\Event;
use JTL\Nachricht\Queue\Client\RabbitMqClient;

class RabbitMqEmitter implements Emitter
{
    /**
     * @var RabbitMqClient
     */
    private $client;

    /**
     * RabbitMqEmitter constructor.
     * @param RabbitMqClient $client
     */
    public function __construct(RabbitMqClient $client)
    {
        $this->client = $client;
    }


    public function emit(Event $event): void
    {
        if ($this->client !== null) {
            $this->client->publish($event);
        }
    }
}
