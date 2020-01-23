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
    public static function getRoutingKey(): string;

    public static function getExchange(): string;

    public function getEventId(): string;

    public function setLastError(string $errorMessage): void;

    public function isDeadLetter(): bool;
}
