<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 21/05/19
 */

namespace JTL\Nachricht\Event;

use JTL\Nachricht\Contracts\Event\Event;

abstract class AbstractEvent implements Event
{
    /**
     * @return string
     */
    public function getRoutingKey(): string
    {
        return $this->getDefaultRoutingKey();
    }

    /**
     * @return string
     */
    public function getExchange(): string
    {
        return '';
    }

    /**
     * @return int
     */
    public function getMaxRetryCount(): int
    {
        return 3;
    }

    /**
     * @return string
     */
    private function getDefaultRoutingKey(): string
    {
        return get_class($this);
    }
}
