<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Dispatcher;

use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Contract\Listener\Listener;
use JTL\Nachricht\Listener\ListenerProvider;
use Psr\EventDispatcher\EventDispatcherInterface;

class RabbitMqDispatcher implements EventDispatcherInterface
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
     * @param Event&object $event
     */
    public function dispatch(object $event)
    {
        /** @var Listener $listener */
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }
    }
}
