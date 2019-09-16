<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/09/10
 */

namespace JTL\Nachricht\Listener\Cache;

use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Contract\Listener\Listener;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;

class ListenerDetector extends NodeVisitorAbstract
{
    /**
     * @var bool
     */
    private $classIsListener = false;

    /**
     * @var array
     */
    private $listenerMethods;

    /**
     * @var string
     */
    private $listenerClass;

    /**
     * @param Node $node
     * @return int|Node|void|null
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            $this->classIsListener = $this->classImplementsListenerInterface($node);

            if ($this->classIsListener) {
                $this->listenerClass = $this->getClassName($node);
            }
        }

        if ($this->classIsListener) {
            if ($node instanceof ClassMethod && $this->classHasHandlerMethod($node)) {
                $this->listenerMethods[] = [
                    'methodName' => $node->name->name,
                    'eventClass' => $this->getArgumentClass($node)
                ];
            }
        }
    }

    /**
     * @return bool
     */
    public function isClassListener(): bool
    {
        return $this->classIsListener;
    }

    /**
     * @return array
     */
    public function getListenerMethods(): array
    {
        return $this->listenerMethods;
    }

    /**
     * @return string
     */
    public function getListenerClass(): string
    {
        return $this->listenerClass;
    }

    /**
     * @param Class_ $class
     * @return string
     */
    private function getClassName(Class_ $class): string
    {
        return implode('\\', $class->namespacedName->parts);
    }

    /**
     * @param Class_ $class
     * @return bool
     */
    private function classImplementsListenerInterface(Class_ $class): bool
    {
        $implementedInterfaces = array_map(function ($implements) {
            return implode('\\', $implements->parts);
        }, $class->implements);

        return in_array(Listener::class, $implementedInterfaces);
    }

    /**
     * @param ClassMethod $classMethod
     * @return string
     */
    private function getArgumentClass(ClassMethod $classMethod): string
    {
        return implode("\\", $classMethod->params[0]->type->parts);
    }

    /**
     * @param ClassMethod $classMethod
     * @return bool
     */
    private function classHasHandlerMethod(ClassMethod $classMethod): bool
    {
        if (!isset($classMethod->params[0])) {
            return false;
        }

        $argumentClass = $this->getArgumentClass($classMethod);
        $implementedInterfaces = class_implements($argumentClass);

        return in_array(Event::class, $implementedInterfaces);
    }
}
