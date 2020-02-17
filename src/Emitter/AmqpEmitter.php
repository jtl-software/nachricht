<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 21/05/19
 */

namespace JTL\Nachricht\Emitter;

use JTL\Nachricht\Contract\Emitter\Emitter;
use JTL\Nachricht\Contract\Message\AmqpTransportableMessage;
use JTL\Nachricht\Contract\Message\Message;
use JTL\Nachricht\Transport\Amqp\AmqpTransport;

class AmqpEmitter implements Emitter
{
    private AmqpTransport $transport;

    public function __construct(AmqpTransport $transport)
    {
        $this->transport = $transport;
    }

    /**
     * @param Message&AmqpTransportableMessage ...$eventList
     */
    public function emit(Message ...$eventList): void
    {
        foreach ($eventList as $event) {
            $this->transport->publish($event);
        }
    }
}
