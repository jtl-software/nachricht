<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/09/11
 */

namespace JTL\Nachricht\Message\Cache;

class MessageCache
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
     * @param string $messageClass
     * @return array
     */
    public function getListenerListForMessage(string $messageClass): array
    {
        return $this->listenerCache[$messageClass]['listenerList'] ?? [];
    }

    /**
     * @return array
     */
    public function getMessageClassList(): array
    {
        return array_keys($this->listenerCache);
    }

    /**
     * @param string $messageClass
     * @return string|null
     */
    public function getRoutingKeyForMessage(string $messageClass): ?string
    {
        return $this->listenerCache[$messageClass]['routingKey'] ?? null;
    }
}
