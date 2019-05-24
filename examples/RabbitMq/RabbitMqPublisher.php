<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

use JTL\Nachricht\Emitter\RabbitMqEmitter;
use JTL\Nachricht\Examples\RabbitMq\Event\CreateFileEvent;

include_once __DIR__ . '/../common/common.php';

/** @var RabbitMqEmitter $rmqEmitter */
$rmqEmitter = $containerBuilder->get(RabbitMqEmitter::class);

foreach (range(1, 200) as $i) {
    $event = new CreateFileEvent($i);
    $rmqEmitter->emit($event);
}
