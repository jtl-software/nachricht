<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Dispatcher;

use JTL\Nachricht\Contract\Dispatcher\MessageDispatcherInterface;
use JTL\Nachricht\Contract\Message\Message;
use JTL\Nachricht\Listener\ListenerProvider;

class AmqpDispatcher implements MessageDispatcherInterface
{
    /**
     * @var ListenerProvider
     */
    private $listenerProvider;


    /**
     * AmqpDispatcher constructor.
     * @param ListenerProvider $listenerProvider
     */
    public function __construct(ListenerProvider $listenerProvider)
    {
        $this->listenerProvider = $listenerProvider;
    }

    /**
     * @param Message&object $message
     */
    public function dispatch(object $message): void
    {
        foreach ($this->listenerProvider->getListenersForMessage($message) as $listener) {
            $listener($message);
        }
    }
}
