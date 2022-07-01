<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

use JTL\Nachricht\Emitter\AmqpEmitter;
use JTL\Nachricht\Examples\Amqp\Message\CreateFileAmqpMessage;
use JTL\Nachricht\Examples\Amqp\Message\DelayedDummyAmqpMessage;
use JTL\Nachricht\Examples\Amqp\Message\DummyRetryDelayAmqpMessage;
use JTL\Nachricht\Examples\Amqp\Message\DummyAmqpMessage;
use JTL\Nachricht\Examples\Amqp\Message\MessageWithoutListener;

include_once __DIR__ . '/../common/common.php';

/** @var AmqpEmitter $rmqEmitter */
$rmqEmitter = $containerBuilder->get(AmqpEmitter::class);

$message = [];
foreach (range(1, 10) as $i) {
    $message[] = new DummyAmqpMessage('Hello world');
    $message[] = new DummyRetryDelayAmqpMessage('Hello world', 4);
    $message[] = new DelayedDummyAmqpMessage('Hello world', 7);
}
#foreach (range(1, 100) as $i) {
#    $message[] = new CreateFileAmqpMessage($i);
#
#}
$rmqEmitter->emit(...$message);
