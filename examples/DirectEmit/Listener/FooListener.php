<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Examples\DirectEmit\Listener;


use JTL\Nachricht\Contract\Listener\Listener;
use JTL\Nachricht\Examples\DirectEmit\Event\FooEvent;

class FooListener implements Listener
{
    public function listen(FooEvent $event): void
    {
        echo 'FooListener called: ' . $event->getFooProperty();
    }
}
