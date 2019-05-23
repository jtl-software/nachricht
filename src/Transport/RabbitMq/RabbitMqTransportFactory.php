<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/23
 */

namespace JTL\Nachricht\Transport\RabbitMq;

use JTL\Nachricht\Contracts\Serializer\EventSerializer;
use JTL\Nachricht\Contracts\Transport\EventTransport;
use JTL\Nachricht\Contracts\Transport\EventTransportFactory;

class RabbitMqTransportFactory implements EventTransportFactory
{
    /**
     * @param array $connectionSettings
     * @param EventSerializer $serializer
     * @return EventTransport
     */
    public function createTransport(array $connectionSettings, EventSerializer $serializer): EventTransport
    {
        return new RabbitMqTransport(
            new RabbitMqConnectionSettings(
                $connectionSettings['host'],
                $connectionSettings['port'],
                $connectionSettings['user'],
                $connectionSettings['password'],
                $connectionSettings['vhost'] ?? '/'
            ),
            $serializer
        );
    }
}
