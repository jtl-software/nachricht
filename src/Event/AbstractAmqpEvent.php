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
    public static function getRoutingKey(): string
    {
        return self::getDefaultRoutingKey();
    }

    /**
     * @return string
     */
    public static function getExchange(): string
    {
        return '';
    }

    /**
     * @return int
     */
    public static function getMaxRetryCount(): int
    {
        return 3;
    }

    /**
     * @return string
     */
    private static function getDefaultRoutingKey(): string
    {
        return static::class;
    }
}
