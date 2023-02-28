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
        'msg__JTL_Nachricht_Examples_Amqp_Message_MessageWithoutListener',
        'msg__JTL_Nachricht_Examples_Amqp_Message_DummyAmqpMessage',
        'msg__JTL_Nachricht_Examples_Amqp_Message_CustomRetryDelayAmqpMessage',
    ),
    1
);

/** @var AmqpConsumer $consumer */
$consumer = $containerBuilder->get(AmqpConsumer::class);

$consumer->consume($subscriptionSettings);
