<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Examples\DirectEmit\Event;

use JTL\Nachricht\Collection\StringCollection;
use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Examples\DirectEmit\Listener\FooListener;

class FooAmqpEvent implements Event
{
    private $fooProperty;

    /**
     * FooEvent constructor.
     * @param $fooProperty
     */
    public function __construct($fooProperty)
    {
        $this->fooProperty = $fooProperty;
    }

    /**
     * @return mixed
     */
    public function getFooProperty()
    {
        return $this->fooProperty;
    }


    public function getListenerClassList(): StringCollection
    {
        return StringCollection::from(FooListener::class);
    }
}
