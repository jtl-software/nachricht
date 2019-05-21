<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

use JTL\Nachricht\Emitter\DirectEmitter;
use JTL\Nachricht\Emitter\RabbitMqEmitter;
use JTL\Nachricht\Examples\RabbitMq\Event\FooEvent;
use JTL\Nachricht\Queue\Client\ConnectionSettings;

require_once __DIR__ . '/common.php';

/** @var DirectEmitter $emitter */
$directEmitter = $containerBuilder->get(DirectEmitter::class);

$connectionSettings = new ConnectionSettings('localhost', 5672, 'guest', 'guest');

$client = $containerBuilder->get(\JTL\Nachricht\Queue\Client\RabbitMqClient::class);
$client->connect($connectionSettings);

/** @var RabbitMqEmitter $rmqEmitter */
$rmqEmitter = $containerBuilder->get(RabbitMqEmitter::class);

$event = new FooEvent(bin2hex(random_bytes(24)));

foreach (range(1, 1) as $i) {
    $rmqEmitter->emit($event);
}
