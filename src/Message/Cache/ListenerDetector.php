<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/09/10
 */

namespace JTL\Nachricht\Message\Cache;

use JTL\Nachricht\Contract\Listener\Listener;
use JTL\Nachricht\Contract\Message\Message;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

class ListenerDetector extends AbstractVisitor
{
    /**
     * @var bool
     */
    private bool $classIsListener = false;

    /**
     * @var array<int, array{methodName: string, eventClass: string}>
     */
    private array $listenerMethods = [];

    /**
     * @var string|null
     */
    private ?string $listenerClass;

    /**
     * @param Node $node
     * @return int|Node|void|null
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            $this->classIsListener = $this->classImplementsInterface($node, Listener::class);

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
     * @return array<int, array{methodName: string, eventClass: string}>
     */
    public function getListenerMethods(): array
    {
        return $this->listenerMethods;
    }

    /**
     * @return string|null
     */
    public function getListenerClass(): ?string
    {
        return $this->listenerClass;
    }

    /**
     * @param ClassMethod $classMethod
     * @return string
     */
    private function getArgumentClass(ClassMethod $classMethod): string
    {
        $typeNode = $classMethod->params[0]->type;

        if (isset($typeNode->name) && $typeNode instanceof Name && $typeNode->getParts() !== null) {
            return $typeNode->toString();
        }


        if (!isset($typeNode->parts)) {
            throw new \RuntimeException('Argument classname is unknown');
        }

        return implode("\\", $typeNode->parts);
    }

    /**
     * @param ClassMethod $classMethod
     * @return bool
     */
    private function classHasHandlerMethod(ClassMethod $classMethod): bool
    {
        if (!$classMethod->isPublic()) {
            return false;
        }

        if (!isset($classMethod->params[0])) {
            return false;
        }

        $argumentClass = $this->getArgumentClass($classMethod);
        $implementedInterfaces = class_implements($argumentClass);
        if ($implementedInterfaces === false) {
            return false;
        }

        return in_array(Message::class, $implementedInterfaces);
    }
}
