<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/10/01
 */

namespace JTL\Nachricht\Message\Cache;

use JTL\Nachricht\Contract\Message\AmqpTransportableMessage;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;

class AmqpMessageRoutingKeyExtractor extends AbstractVisitor
{
    private bool $classIsMessage = false;
    private ?string $messageClass;
    private string $routingKey;

    /**
     * @param Node $node
     * @return int|Node|void|null
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Class_ && !$node->isAbstract()) {
            $this->classIsMessage = $this->classImplementsInterface($node, AmqpTransportableMessage::class);

            if ($this->classIsMessage) {
                $this->messageClass = $this->getClassName($node);
            }
        }

        if ($this->classIsMessage) {
            $getRoutingKeyFunction = $this->messageClass . '::getRoutingKey';
            if (is_callable($getRoutingKeyFunction)) {
                $this->routingKey = $getRoutingKeyFunction();
            }
        }
    }

    /**
     * @return bool
     */
    public function isClassMessage(): bool
    {
        return $this->classIsMessage;
    }

    /**
     * @return string|null
     */
    public function getMessageClass(): ?string
    {
        return $this->messageClass;
    }

    /**
     * @return string
     */
    public function getRoutingKey(): string
    {
        return $this->routingKey;
    }
}
