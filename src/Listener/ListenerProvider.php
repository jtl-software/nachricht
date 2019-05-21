<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Listener;


use JTL\Nachricht\Contracts\Event\Event;
use Psr\Container\ContainerInterface;

class ListenerProvider
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param Event $event
     * @return \Traversable
     */
    public function getListenersForEvent(Event $event): \Traversable
    {
        foreach ($event->getListenerClassList() as $listenerClass) {
            yield $this->container->get($listenerClass);
        }
    }
}