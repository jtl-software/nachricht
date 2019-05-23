<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 21/05/19
 */

namespace JTL\Nachricht\Transport;

use JTL\Nachricht\Collection\StringCollection;

class SubscriptionSettings
{
    /**
     * @var StringCollection
     */
    private $queueNameList;

    /**
     * SubscriptionSettings constructor.
     * @param StringCollection $queueNameList
     */
    public function __construct(StringCollection $queueNameList)
    {
        $this->queueNameList = $queueNameList;
    }

    /**
     * @return StringCollection
     */
    public function getQueueNameList(): StringCollection
    {
        return $this->queueNameList;
    }
}
