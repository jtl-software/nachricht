<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 21/05/19
 */

namespace JTL\Nachricht\Emitter;

use JTL\Nachricht\Contract\Emitter\Emitter;
use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Transport\RabbitMq\RabbitMqTransport;

class RabbitMqEmitter implements Emitter
{
    /**
     * @var RabbitMqTransport
     */
    private $transport;

    /**
     * RabbitMqEmitter constructor.
     * @param RabbitMqTransport $client
     */
    public function __construct(RabbitMqTransport $client)
    {
        $this->transport = $client;
    }

    /**
     * @param Event $event
     */
    public function emit(Event $event): void
    {
        $this->transport->publish($event);
    }
}
