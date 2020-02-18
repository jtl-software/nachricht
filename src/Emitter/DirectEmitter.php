<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Emitter;

use JTL\Nachricht\Contract\Dispatcher\MessageDispatcherInterface;
use JTL\Nachricht\Contract\Emitter\Emitter;
use JTL\Nachricht\Contract\Message\Message;
use JTL\Nachricht\Listener\ListenerProvider;

class DirectEmitter implements Emitter, MessageDispatcherInterface
{
    private ListenerProvider $listenerProvider;

    public function __construct(ListenerProvider $listenerProvider)
    {
        $this->listenerProvider = $listenerProvider;
    }

    public function emit(Message ...$messageList): void
    {
        foreach ($messageList as $message) {
            $this->dispatch($message);
        }
    }

    public function dispatch(Message $message): void
    {
        foreach ($this->listenerProvider->getListenersForMessage($message) as $listener) {
            $listener($message);
        }
    }
}
