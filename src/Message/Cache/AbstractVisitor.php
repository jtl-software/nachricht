<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/10/01
 */

namespace JTL\Nachricht\Message\Cache;

use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;

abstract class AbstractVisitor extends NodeVisitorAbstract
{
    /**
     * @param Class_ $class
     * @param string $interface
     * @return bool
     */
    protected function classImplementsInterface(Class_ $class, string $interface): bool
    {
        $className = $this->getClassName($class);
        if ($className === null) {
            return false;
        }

        $interfaceList = @class_implements($className);
        if ($interfaceList === false) {
            return false;
        }

        return in_array($interface, $interfaceList, true);
    }

    /**
     * @param Class_ $class
     * @return string|null
     */
    protected function getClassName(Class_ $class): ?string
    {
        if (!isset($class->namespacedName->parts)) {
            return null;
        }

        return implode('\\', $class->namespacedName->parts);
    }
}
