<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

use JTL\Nachricht\Collection\StringCollection;
use JTL\Nachricht\Queue\Client\ConnectionSettings;
use JTL\Nachricht\Queue\Client\SubscriptionSettings;
use JTL\Nachricht\Queue\Poller\RabbitMqPoller;

require_once __DIR__ . '/common.php';

$connectionSettings = new ConnectionSettings('localhost', 5672, 'guest', 'guest');
$subscriptionSettings = new SubscriptionSettings(StringCollection::from('test_queue'));

/** @var RabbitMqPoller $poller */
$poller = $containerBuilder->get(RabbitMqPoller::class);

$poller->run($connectionSettings, $subscriptionSettings);