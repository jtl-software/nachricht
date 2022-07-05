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
use JTL\Nachricht\Examples\Amqp\Message\CustomRetryDelayAmqpMessage;

class DummyListener implements Listener
{
    public function listen(DummyAmqpMessage $message): void
    {
        echo 'Dummy Listener called: ' . $message->getData() . "\n";
    }

    public function listen2(CustomRetryDelayAmqpMessage $message): void
    {
        echo "Failed Message with Retry Deplay";

        if (random_int(1, 3) >= 2) {
            throw new Exception("This message is getting delayed during re-queue");
        }
    }
}
