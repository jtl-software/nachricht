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
     * @var StringCollection<string>
     */
    private $queueNameList;

    /**
     * SubscriptionSettings constructor.
     * @param StringCollection<string> $queueNameList
     */
    public function __construct(StringCollection $queueNameList)
    {
        $this->queueNameList = $queueNameList;
    }

    /**
     * @return StringCollection<string>
     */
    public function getQueueNameList(): StringCollection
    {
        return $this->queueNameList;
    }
}
