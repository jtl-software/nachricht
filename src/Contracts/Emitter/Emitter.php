<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Contracts\Emitter;

use JTL\Nachricht\Contracts\Event\Event;

interface Emitter
{
    /**
     * @param Event $event
     */
    public function emit(Event $event): void;
}
