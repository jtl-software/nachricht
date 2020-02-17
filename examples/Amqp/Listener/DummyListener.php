<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Examples\Amqp\Listener;


use JTL\Nachricht\Contract\Listener\Listener;
use JTL\Nachricht\Examples\Amqp\Message\DummyAmqpMessage;

class DummyListener implements Listener
{
    /**
     * @param DummyAmqpMessage $event
     */
    public function listen(DummyAmqpMessage $event): void
    {
        echo 'Dummy Listener called: ' . $event->getData() . "\n";
    }
}
