<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

use JTL\Nachricht\Emitter\DirectEmitter;
use JTL\Nachricht\Examples\DirectEmit\Event\FooEvent;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

require_once '../../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();

$loader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__));

$loader->load('service.yaml');

$containerBuilder->compile();

/** @var DirectEmitter $emitter */
$emitter = $containerBuilder->get(DirectEmitter::class);

$event = new FooEvent('Test');

$emitter->emit($event);