<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/23
 */

namespace JTL\Nachricht\Transport\Amqp;

use JTL\Nachricht\Contract\Serializer\EventSerializer;

class AmqpTransportFactory
{
    /**
     * @param array $connectionSettings
     * @param EventSerializer $serializer
     * @return AmqpTransport
     */
    public function createTransport(array $connectionSettings, EventSerializer $serializer): AmqpTransport
    {
        return new AmqpTransport(
            new AmqpConnectionSettings(
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
