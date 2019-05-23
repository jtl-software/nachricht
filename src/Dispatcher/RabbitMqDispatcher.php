<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Dispatcher;

use JTL\Nachricht\Contracts\Dispatcher\Dispatcher;
use JTL\Nachricht\Contracts\Event\Event;
use JTL\Nachricht\Listener\ListenerProvider;

class RabbitMqDispatcher implements Dispatcher
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

    /**
     * @param Event $event
     * @return bool
     */
    public function dispatch(Event $event): bool
    {
        return iterator_to_array($this->listenerProvider->getListenersForEvent($event))[0]->execute($event);
    }
}
