<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Emitter;


use JTL\Nachricht\Contracts\Emitter\Emitter;
use JTL\Nachricht\Contracts\Event\Event;
use JTL\Nachricht\Contracts\Listener\Listener;
use JTL\Nachricht\Listener\ListenerProvider;

class DirectEmitter implements Emitter
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
        /** @var Listener $listener */
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            $listener->execute($event);
        }
    }
}
