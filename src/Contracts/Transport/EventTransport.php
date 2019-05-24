<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/17
 */

namespace JTL\Nachricht\Contracts\Transport;

use Closure;
use JTL\Nachricht\Contracts\Event\Event;
use JTL\Nachricht\Serializer\Exception\DeserializationFailedException;
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

    /**
     * @throws DeserializationFailedException
     */
    public function poll(): void;
}
