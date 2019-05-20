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
$emitter = $containerBuilder->get(\JTL\Nachricht\Emitter\DirectEmitter::class);

$event = new \JTL\Nachricht\Event\FooEvent();

$emitter->emit($event);