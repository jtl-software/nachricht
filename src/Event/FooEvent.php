<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Event;


use JTL\Nachricht\Collection\StringCollection;
use JTL\Nachricht\Contracts\Event\Event;
use JTL\Nachricht\Listener\FooListener;

class FooEvent implements Event
{

    public function getListenerClassList(): StringCollection
    {
        $col = new StringCollection();
        $col[] = FooListener::class;
        return $col;
    }
}