<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

use JTL\Nachricht\Emitter\AmqpEmitter;
use JTL\Nachricht\Examples\Amqp\Message\CreateFileAmqpMessage;
use JTL\Nachricht\Examples\Amqp\Message\Dummy2AmqpMessage;
use JTL\Nachricht\Examples\Amqp\Message\DummyAmqpMessage;
use JTL\Nachricht\Examples\Amqp\Message\MessageWithoutListener;

include_once __DIR__ . '/../common/common.php';

/** @var AmqpEmitter $rmqEmitter */
$rmqEmitter = $containerBuilder->get(AmqpEmitter::class);

$message = [];
foreach (range(1, 10) as $i) {
    $message[] = new DummyAmqpMessage('Hello world');
    $message[] = new Dummy2AmqpMessage('Hello world');
}
#foreach (range(1, 100) as $i) {
#    $message[] = new CreateFileAmqpMessage($i);
#
#}
$rmqEmitter->emit(...$message);
