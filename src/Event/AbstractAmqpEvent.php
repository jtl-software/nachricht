<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 21/05/19
 */

namespace JTL\Nachricht\Event;

use JTL\Nachricht\Contract\Event\AmqpEvent;

abstract class AbstractAmqpEvent implements AmqpEvent
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
