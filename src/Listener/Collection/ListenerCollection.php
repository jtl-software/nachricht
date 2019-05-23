<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Listener\Collection;

use JTL\Generic\GenericCollection;
use JTL\Nachricht\Contracts\Listener\Listener;

class ListenerCollection extends GenericCollection
{
    /**
     * ListenerCollection constructor.
     */
    public function __construct()
    {
        parent::__construct(Listener::class);
    }
}
