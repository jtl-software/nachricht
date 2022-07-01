<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Examples\Amqp\Listener;


use Exception;
use JTL\Nachricht\Contract\Listener\Listener;
use JTL\Nachricht\Examples\Amqp\Message\DelayedDummyAmqpMessage;
use JTL\Nachricht\Examples\Amqp\Message\DummyAmqpMessage;
use JTL\Nachricht\Examples\Amqp\Message\DummyRetryDelayAmqpMessage;

class DummyListener implements Listener
{
    public function listen(DummyAmqpMessage $message): void
    {
//        throw new Exception(uniqid('message_1', true));
        echo 'Dummy Listener called: ' . $message->getData() . "\n";
    }

    public function listen2(DummyRetryDelayAmqpMessage $message): void
    {
//        throw new Exception(uniqid('message_2', true));
        echo 'Dummy Listener with retry delay called: ' . $message->getData() . "\n";
    }

    public function listen3(DelayedDummyAmqpMessage $message): void
    {
//        throw new Exception(uniqid('message_2', true));
        echo 'Delayed Dummy Listener called: ' . $message->getData() . "\n";
    }
}
