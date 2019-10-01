<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/09/11
 */

namespace JTL\Nachricht\Event\Cache;

class EventCache
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
        return $this->listenerCache[$eventClass]['listenerList'] ?? [];
    }

    /**
     * @return array
     */
    public function getEventClassList(): array
    {
        return array_keys($this->listenerCache);
    }

    /**
     * @param string $eventClass
     * @return string|null
     */
    public function getRoutingKeyForEvent(string $eventClass): ?string
    {
        return $this->listenerCache[$eventClass]['routingKey'] ?? null;
    }
}
