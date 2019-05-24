<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Contracts\Listener;

use JTL\Nachricht\Contracts\Event\Event;

interface Listener
{
    /**
     * @param Event $event
     * @return bool
     */
    public function execute(Event $event): bool;
}
