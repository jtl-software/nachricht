<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

use JTL\Nachricht\Emitter\AmqpEmitter;
use JTL\Nachricht\Examples\Amqp\Message\CreateFileAmqpMessage;

include_once __DIR__ . '/../common/common.php';

/** @var AmqpEmitter $rmqEmitter */
$rmqEmitter = $containerBuilder->get(AmqpEmitter::class);

$message = [];
foreach (range(1, 100) as $i) {
    $message[] = new CreateFileAmqpMessage($i);

}
$rmqEmitter->emit(...$message);
