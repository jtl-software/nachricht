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
    public function getRoutingKey(): string
    {
        return '';
    }

    public function getExchange(): string
    {
        return '';
    }

    public function getMaxRetryCount(): int
    {
        return 3;
    }
}
