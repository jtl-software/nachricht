<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Listener;

use JTL\Nachricht\Event\Cache\EventCache;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class ListenerProvider implements ListenerProviderInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EventCache
     */
    private $listenerCache;

    /**
     * ListenerProvider constructor.
     * @param ContainerInterface $container
     * @param EventCache $listenerCache
     */
    public function __construct(ContainerInterface $container, EventCache $listenerCache)
    {
        $this->container = $container;
        $this->listenerCache = $listenerCache;
    }

    /**
     * @param object $event
     * @return iterable
     */
    public function getListenersForEvent(object $event): iterable
    {
        foreach ($this->listenerCache->getListenerListForEvent(get_class($event)) as $listener) {
            $listenerInstance = $this->container->get($listener['listenerClass']);
            $method = $listener['method'];

            yield function (object $event) use ($listenerInstance, $method) {
                $listenerInstance->{$method}($event);
            };
        }
    }

    /**
     * @param object $event
     * @return bool
     */
    public function eventHasListeners(object $event): bool
    {
        return count($this->listenerCache->getListenerListForEvent(get_class($event))) > 0;
    }
}
