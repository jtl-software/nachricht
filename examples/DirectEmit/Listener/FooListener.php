<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Examples\DirectEmit\Listener;


use JTL\Nachricht\Contracts\Event\Event;
use JTL\Nachricht\Contracts\Listener\Listener;

class FooListener implements Listener
{

    public function execute(Event $event): bool
    {
        echo 'FooListener called: ' . $event->getFooProperty();
        throw new \Exception();
        return true;
    }
}