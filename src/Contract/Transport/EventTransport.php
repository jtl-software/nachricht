<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/17
 */

namespace JTL\Nachricht\Contract\Transport;

use Closure;
use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Transport\SubscriptionSettings;

interface EventTransport
{
    /**
     * @param Event $event
     */
    public function publish(Event $event): void;

    /**
     * @param SubscriptionSettings $subscriptionOptions
     * @param Closure $handler
     * @return EventTransport
     */
    public function subscribe(SubscriptionSettings $subscriptionOptions, Closure $handler): self;

    public function poll(): void;
}
