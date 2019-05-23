<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/22
 */

namespace JTL\Nachricht\Contracts\Dispatcher;

use JTL\Nachricht\Contracts\Event\Event;

interface Dispatcher
{
    /**
     * @param Event $event
     * @return bool
     */
    public function dispatch(Event $event): bool;
}
