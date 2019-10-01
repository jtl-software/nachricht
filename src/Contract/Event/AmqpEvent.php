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
    public static function getRoutingKey(): string;

    /**
     * @return string
     */
    public static function getExchange(): string;

    /**
     * @return int
     */
    public static function getMaxRetryCount(): int;
}
