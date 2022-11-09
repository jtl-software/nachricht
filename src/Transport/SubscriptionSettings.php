<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 21/05/19
 */

namespace JTL\Nachricht\Transport;

use JTL\Generic\StringCollection;

class SubscriptionSettings
{
    /**
     * SubscriptionSettings constructor.
     * @param StringCollection<string> $queueNameList
     * @param int $ttl seconds
     */
    public function __construct(private StringCollection $queueNameList, private int $ttl = -1)
    {
    }

    /**
     * @return StringCollection<string>
     */
    public function getQueueNameList(): StringCollection
    {
        return $this->queueNameList;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }
}
