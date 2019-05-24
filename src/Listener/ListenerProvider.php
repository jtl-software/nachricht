<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Listener;

use JTL\Nachricht\Contract\Event\Event;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class ListenerProvider implements ListenerProviderInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * ListenerProvider constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param Event&object $event
     * @return iterable
     */
    public function getListenersForEvent(object $event): iterable
    {
        foreach ($event->getListenerClassList() as $listenerClass) {
            yield $this->container->get($listenerClass);
        }
    }
}
