<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Collection;

use JTL\Generic\GenericCollection;

class StringCollection extends GenericCollection
{
    public function __construct()
    {
        parent::__construct('string');
    }
}
