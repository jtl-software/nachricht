<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Examples\DirectEmit\Listener;


use JTL\Nachricht\Contract\Listener\Listener;
use JTL\Nachricht\Examples\DirectEmit\Message\FooMessage;

class FooListener implements Listener
{
    public function listen(FooMessage $message): void
    {
        echo 'FooListener called: ' . $message->getFooProperty();
    }
}
