<?php
/**
 * This File is part of JTL-Software
 *
 * User: rherrgesell
 * Date: 6/3/19
 */

namespace JTL\Nachricht\Contract\Event;

interface AmqpEvent extends Event
{
    /**
     * @return string
     */
    public function getRoutingKey(): string;

    /**
     * @return string
     */
    public function getExchange(): string;

    /**
     * @return int
     */
    public function getMaxRetryCount(): int;
}
