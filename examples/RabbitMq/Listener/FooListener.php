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

class FooListener implements Listener
{

    public function execute(Event $event): bool
    {
        $handle = fopen(__DIR__ . '/../tmp/' . $event->getData(), 'w+');
        fwrite($handle, bin2hex(random_bytes(1024 * 1024)));
        fclose($handle);
        usleep(random_int(50, 800) * 1000);

        echo "Created file {$event->getData()}\n";

        return true;
    }
}