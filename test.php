<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

require_once './vendor/autoload.php';

$containerBuilder = new ContainerBuilder();

$loader = new \Symfony\Component\DependencyInjection\Loader\YamlFileLoader($containerBuilder, new \Symfony\Component\Config\FileLocator(__DIR__));

$loader->load('service.yaml');

$containerBuilder->compile();

/** @var \JTL\Nachricht\Emitter\DirectEmitter $emitter */
$directEmitter = $containerBuilder->get(\JTL\Nachricht\Emitter\DirectEmitter::class);

$connectionSettings = new \JTL\Nachricht\Queue\Client\ConnectionSettings('localhost', 5672, 'guest', 'guest');
$client = new \JTL\Nachricht\Queue\Client\RabbitMqClient();
$client->connect($connectionSettings);

/** @var \JTL\Nachricht\Emitter\RabbitMqEmitter $rmqEmitter */
$rmqEmitter = $containerBuilder->get(\JTL\Nachricht\Emitter\RabbitMqEmitter::class);
$rmqEmitter->setClient($client);

$event = new \JTL\Nachricht\Event\FooEvent('Hello world');

$rmqEmitter->emit($event);
$client->subscribe(['queueName' => 'foo_queue']);

