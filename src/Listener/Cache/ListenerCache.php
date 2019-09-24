<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/09/11
 */

namespace JTL\Nachricht\Listener\Cache;

class ListenerCache
{
    /**
     * @var array
     */
    private $listenerCache;

    /**
     * ListenerCache constructor.
     * @param array $listenerCache
     */
    public function __construct(array $listenerCache)
    {
        $this->listenerCache = $listenerCache;
    }

    /**
     * @param string $eventClass
     * @return array
     */
    public function getListenerListForEvent(string $eventClass): array
    {
        return $this->listenerCache[$eventClass] ?? [];
    }
}
