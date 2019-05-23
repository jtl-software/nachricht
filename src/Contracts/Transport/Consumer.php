<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/22
 */

namespace JTL\Nachricht\Contracts\Transport;

use JTL\Nachricht\Transport\SubscriptionSettings;

interface Consumer
{
    public function consume(SubscriptionSettings $subscriptionSettings): void;
}
