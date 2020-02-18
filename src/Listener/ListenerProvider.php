<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Listener;

use JTL\Nachricht\Contract\Listener\ListenerProviderInterface;
use JTL\Nachricht\Contract\Message\Message;
use JTL\Nachricht\Contract\Hook\AfterMessageErrorHook;
use JTL\Nachricht\Contract\Hook\AfterMessageHook;
use JTL\Nachricht\Contract\Hook\BeforeMessageHook;
use JTL\Nachricht\Message\Cache\MessageCache;
use Psr\Container\ContainerInterface;

class ListenerProvider implements ListenerProviderInterface
{
    private ContainerInterface $container;
    private MessageCache $listenerCache;

    public function __construct(ContainerInterface $container, MessageCache $listenerCache)
    {
        $this->container = $container;
        $this->listenerCache = $listenerCache;
    }

    public function getListenersForMessage(Message $message): iterable
    {
        foreach ($this->listenerCache->getListenerListForMessage(get_class($message)) as $listener) {
            $listenerInstance = $this->container->get($listener['listenerClass']);
            $method = $listener['method'];

            yield function (Message $message) use ($listenerInstance, $method) {
                try {
                    if ($listenerInstance instanceof BeforeMessageHook) {
                        $listenerInstance->setup($message);
                    }

                    $listenerInstance->{$method}($message);
                } catch (\Throwable $exception) {
                    if ($listenerInstance instanceof AfterMessageErrorHook) {
                        $listenerInstance->onError($message, $exception);
                    } else {
                        throw $exception;
                    }
                } finally {
                    if ($listenerInstance instanceof AfterMessageHook) {
                        $listenerInstance->after($message);
                    }
                }
            };
        }
    }

    public function eventHasListeners(Message $message): bool
    {
        return count($this->listenerCache->getListenerListForMessage(get_class($message))) > 0;
    }
}
