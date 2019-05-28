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
use JTL\Nachricht\Transport\Amqp\AmqpTransport;

class AmqpEmitter implements Emitter
{
    /**
     * @var AmqpTransport
     */
    private $transport;


    /**
     * AmqpEmitter constructor.
     * @param AmqpTransport $transport
     */
    public function __construct(AmqpTransport $transport)
    {
        $this->transport = $transport;
    }

    /**
     * @param Event $event
     */
    public function emit(Event $event): void
    {
        $this->transport->publish($event);
    }
}
