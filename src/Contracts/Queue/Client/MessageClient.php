<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/17
 */

namespace JTL\Nachricht\Contracts\Queue\Client;

use Closure;
use JTL\Nachricht\Contracts\Event\Event;
use JTL\Nachricht\Queue\Client\ConnectionSettings;
use JTL\Nachricht\Queue\Client\SubscriptionSettings;

interface MessageClient
{
    public function connect(ConnectionSettings $connectionSettings): self;
    public function publish(Event $event): void;
    public function subscribe(SubscriptionSettings $subscriptionOptions, Closure $handler): self;
    public function poll(): void;
}