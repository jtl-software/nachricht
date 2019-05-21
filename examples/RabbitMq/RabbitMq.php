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
use JTL\Nachricht\Queue\Poller\RabbitMqPoller;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

require_once '../../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();

$loader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__));

$loader->load('service.yaml');

$containerBuilder->compile();

/** @var DirectEmitter $emitter */
$directEmitter = $containerBuilder->get(DirectEmitter::class);

$connectionSettings = new ConnectionSettings('localhost', 5672, 'guest', 'guest');

$client = $containerBuilder->get(\JTL\Nachricht\Queue\Client\RabbitMqClient::class);
$client->connect($connectionSettings);

/** @var RabbitMqPoller $poller */
$poller = $containerBuilder->get(RabbitMqPoller::class);

/** @var RabbitMqEmitter $rmqEmitter */
$rmqEmitter = $containerBuilder->get(RabbitMqEmitter::class);

$event = new FooEvent('Hello world');

$rmqEmitter->emit($event);
$poller->run($connectionSettings);
