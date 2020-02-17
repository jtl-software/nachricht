<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 21/05/19
 */

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

require_once __DIR__ . '/../vendor/autoload.php';

define('PROJECT_ROOT_AMQP', __DIR__ . '/../Amqp');
define('PROJECT_ROOT_DIRECT', __DIR__ . '/../DirectEmit');
define('CACHE_PATH', __DIR__ . '/../cache.php');

$containerBuilder = new ContainerBuilder();

$loader = new YamlFileLoader($containerBuilder, new FileLocator(__DIR__));

$loader->load('service.yaml');

$containerBuilder->compile();
