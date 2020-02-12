<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Listener;

use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Contract\Hook\AfterEventErrorHook;
use JTL\Nachricht\Contract\Hook\AfterEventHook;
use JTL\Nachricht\Contract\Hook\BeforeEventHook;
use JTL\Nachricht\Event\Cache\EventCache;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class ListenerProvider implements ListenerProviderInterface
{
    private ContainerInterface $container;
    private EventCache $listenerCache;

    public function __construct(ContainerInterface $container, EventCache $listenerCache)
    {
        $this->container = $container;
        $this->listenerCache = $listenerCache;
    }

    public function getListenersForEvent(object $event): iterable
    {
        foreach ($this->listenerCache->getListenerListForEvent(get_class($event)) as $listener) {
            $listenerInstance = $this->container->get($listener['listenerClass']);
            $method = $listener['method'];

            yield function (object $event) use ($listenerInstance, $method) {
                try {
                    if ($listenerInstance instanceof BeforeEventHook && $event instanceof Event) {
                        $listenerInstance->setup($event);
                    }

                    $listenerInstance->{$method}($event);
                } catch (\Throwable $exception) {
                    if ($listenerInstance instanceof AfterEventErrorHook && $event instanceof Event) {
                        $listenerInstance->onError($event, $exception);
                    } else {
                        throw $exception;
                    }
                } finally {
                    if ($listenerInstance instanceof AfterEventHook && $event instanceof Event) {
                        $listenerInstance->after($event);
                    }
                }
            };
        }
    }

    public function eventHasListeners(object $event): bool
    {
        return count($this->listenerCache->getListenerListForEvent(get_class($event))) > 0;
    }
}
