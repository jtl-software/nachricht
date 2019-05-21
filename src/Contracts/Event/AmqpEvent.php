<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 21/05/19
 */

namespace JTL\Nachricht\Contracts\Event;


interface AmqpEvent extends Event
{
    public function getRoutingKey(): string;
    public function getExchange(): string;
}
