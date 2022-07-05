<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

use JTL\Nachricht\Emitter\AmqpEmitter;
use JTL\Nachricht\Examples\Amqp\Message\CustomRetryDelayAmqpMessage;
use JTL\Nachricht\Examples\Amqp\Message\DummyAmqpMessage;

include_once __DIR__ . '/../common/common.php';

/** @var AmqpEmitter $rmqEmitter */
$rmqEmitter = $containerBuilder->get(AmqpEmitter::class);

$message = [];

$message[] = new DummyAmqpMessage(data: 'A message');
$message[] = new CustomRetryDelayAmqpMessage(data: 'Must fail');
$message[] = new DummyAmqpMessage(data: 'With some delay', delay: 30);
$message[] = new DummyAmqpMessage(data: 'Without delay', delay: 0);

$rmqEmitter->emit(...$message);
