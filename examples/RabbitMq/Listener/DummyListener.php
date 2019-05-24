<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Examples\RabbitMq\Listener;


use JTL\Nachricht\Contracts\Event\Event;
use JTL\Nachricht\Contracts\Listener\Listener;
use JTL\Nachricht\Examples\RabbitMq\Event\DummyEvent;

class DummyListener implements Listener
{

    /**
     * @param DummyEvent|Event $event
     * @return bool
     */
    public function execute(Event $event): bool
    {
        echo 'Dummy Listener called: ' . $event->getData() . "\n";
        return true;
    }
}