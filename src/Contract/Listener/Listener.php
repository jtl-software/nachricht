<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Contract\Listener;

use JTL\Nachricht\Contract\Event\Event;

interface Listener
{
    /**
     * @param Event $event
     * @return void
     */
    public function __invoke(Event $event): void;
}
