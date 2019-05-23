<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/17
 */

namespace JTL\Nachricht\Contracts\Event;

use JTL\Nachricht\Collection\StringCollection;

interface Event
{
    public function getListenerClassList(): StringCollection;
    public function getRoutingKey(): string;
    public function getExchange(): string;
    public function getMaxRetryCount(): int;
}
