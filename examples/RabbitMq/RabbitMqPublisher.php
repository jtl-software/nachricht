<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

use JTL\Nachricht\Emitter\RabbitMqEmitter;
use JTL\Nachricht\Examples\RabbitMq\Event\BarEvent;
use JTL\Nachricht\Examples\RabbitMq\Event\FooEvent;

include_once __DIR__ . '/../common/common.php';

/** @var RabbitMqEmitter $rmqEmitter */
$rmqEmitter = $containerBuilder->get(RabbitMqEmitter::class);

$barEvent = new BarEvent('event2');

foreach (range(1, 1000) as $i) {
    $event = new FooEvent($i);
    $rmqEmitter->emit($event);
}
