<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

use JTL\Nachricht\Collection\StringCollection;
use JTL\Nachricht\Transport\RabbitMq\RabbitMqConsumer;
use JTL\Nachricht\Transport\SubscriptionSettings;

include_once __DIR__ . '/../common/common.php';

$subscriptionSettings = new SubscriptionSettings(StringCollection::from('msg__JTL\Nachricht\Examples\RabbitMq\Event\BarEvent', 'msg__test_queue'));

/** @var RabbitMqConsumer $consumer */
$consumer = $containerBuilder->get(RabbitMqConsumer::class);

$consumer->consume($subscriptionSettings);