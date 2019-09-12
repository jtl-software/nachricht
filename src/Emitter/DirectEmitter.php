<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Emitter;

use JTL\Nachricht\Contract\Emitter\Emitter;
use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Listener\ListenerProvider;
use Psr\EventDispatcher\EventDispatcherInterface;

class DirectEmitter implements Emitter, EventDispatcherInterface
{
    /**
     * @var ListenerProvider
     */
    private $listenerProvider;

    /**
     * DirectEmitter constructor.
     * @param ListenerProvider $listenerProvider
     */
    public function __construct(ListenerProvider $listenerProvider)
    {
        $this->listenerProvider = $listenerProvider;
    }

    /**
     * @param Event $event
     */
    public function emit(Event $event): void
    {
        $this->dispatch($event);
    }

    /**
     * @param Event&object $event
     */
    public function dispatch(object $event)
    {
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }
    }
}
