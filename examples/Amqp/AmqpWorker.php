<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

use JTL\Generic\StringCollection;
use JTL\Nachricht\Transport\Amqp\AmqpConsumer;
use JTL\Nachricht\Transport\SubscriptionSettings;

include_once __DIR__ . '/../common/common.php';

$subscriptionSettings = new SubscriptionSettings(
    StringCollection::from(
        'msg__test_queue',
        'msg__JTL\Nachricht\Examples\Amqp\Message\MessageWithoutListener',
        'msg__JTL\Nachricht\Examples\Amqp\Message\DummyAmqpMessage',
        'msg__JTL\Nachricht\Examples\Amqp\Message\Dummy2AmqpMessage',
    )
);

/** @var AmqpConsumer $consumer */
$consumer = $containerBuilder->get(AmqpConsumer::class);

$consumer->consume($subscriptionSettings);
