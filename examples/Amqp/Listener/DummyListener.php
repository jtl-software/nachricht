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
use JTL\Nachricht\Examples\Amqp\Message\DummyAmqpMessage;
use JTL\Nachricht\Examples\Amqp\Message\Dummy2AmqpMessage;

class DummyListener implements Listener
{
    public function listen(DummyAmqpMessage $message): void
    {
//        throw new Exception(uniqid('message_1', true));
        echo 'Dummy Listener called: ' . $message->getData() . "\n";
    }

    public function listen2(Dummy2AmqpMessage $message): void
    {
//        throw new Exception(uniqid('message_2', true));
        echo 'Dummy Listener called: ' . $message->getData() . "\n";
    }
}
