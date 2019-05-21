<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Dispatcher;


use JTL\Nachricht\Contracts\Event\Event;
use JTL\Nachricht\Contracts\Listener\Listener;
use JTL\Nachricht\Listener\ListenerProvider;

class RabbitMqDispatcher
{
    /**
     * @var ListenerProvider
     */
    private $listenerProvider;

    /**
     * RabbitMqDispatcher constructor.
     * @param ListenerProvider $listenerProvider
     */
    public function __construct(ListenerProvider $listenerProvider)
    {
        $this->listenerProvider = $listenerProvider;
    }

    public function dispatch(Event $event)
    {
        /** @var Listener $listener */
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            $listener->execute($event);
            break;
        }
    }
}