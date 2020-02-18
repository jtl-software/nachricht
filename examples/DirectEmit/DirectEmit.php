<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

use JTL\Nachricht\Emitter\DirectEmitter;
use JTL\Nachricht\Examples\DirectEmit\Message\FooMessage;

include_once __DIR__ . '/../common/common.php';

/** @var DirectEmitter $emitter */
$emitter = $containerBuilder->get(DirectEmitter::class);

$message = new FooMessage('Test');

$emitter->emit($message);
