<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Examples\Amqp\Listener;


use JTL\Nachricht\Contract\Event\Event;
use JTL\Nachricht\Contract\Listener\Listener;
use JTL\Nachricht\Examples\Amqp\Event\DummyEvent;

class DummyListener implements Listener
{
    /**
     * @param DummyEvent|Event $event
     * @return void
     */
    public function __invoke(Event $event): void
    {
        echo 'Dummy Listener called: ' . $event->getData() . "\n";
    }
}