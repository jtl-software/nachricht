<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/17
 */

namespace JTL\Nachricht\Contract\Event;

use JTL\Nachricht\Collection\StringCollection;

interface Event
{
    /**
     * @return StringCollection
     */
    public function getListenerClassList(): StringCollection;

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
