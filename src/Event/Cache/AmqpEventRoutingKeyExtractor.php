<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/10/01
 */

namespace JTL\Nachricht\Event\Cache;

use JTL\Nachricht\Contract\Event\AmqpEvent;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;

class AmqpEventRoutingKeyExtractor extends AbstractVisitor
{
    /**
     * @var bool
     */
    private $classIsEvent = false;

    /**
     * @var string|null
     */
    private $eventClass;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @param Node $node
     * @return int|Node|void|null
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Class_ && !$node->isAbstract()) {
            $this->classIsEvent = $this->classImplementsInterface($node, AmqpEvent::class);

            if ($this->classIsEvent) {
                $this->eventClass = $this->getClassName($node);
            }
        }

        if ($this->classIsEvent) {
            $getRoutingKeyFunction = $this->eventClass . '::getRoutingKey';
            if (is_callable($getRoutingKeyFunction)) {
                $this->routingKey = $getRoutingKeyFunction();
            }
        }
    }

    /**
     * @return bool
     */
    public function isClassEvent(): bool
    {
        return $this->classIsEvent;
    }

    /**
     * @return string|null
     */
    public function getEventClass(): ?string
    {
        return $this->eventClass;
    }

    /**
     * @return string
     */
    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }
}
