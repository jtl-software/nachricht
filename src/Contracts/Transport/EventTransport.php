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
use JTL\Nachricht\Transport\SubscriptionSettings;

interface EventTransport
{
    public function publish(Event $event): void;
    public function subscribe(SubscriptionSettings $subscriptionOptions, Closure $handler): self;
    public function poll(): void;
}
