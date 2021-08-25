<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: marius
 * Date: 7/27/21
 */

namespace JTL\Nachricht\Contract\Transport\Amqp;

use JTL\Nachricht\Transport\Amqp\AmqpHttpConnectionFailedException;

interface AmqpQueueLister
{
    /**
     * @param string|null $queuePrefix
     * @return string[]
     *
     * @throws AmqpHttpConnectionFailedException
     */
    public function listQueues(string $queuePrefix = null): array;
}
