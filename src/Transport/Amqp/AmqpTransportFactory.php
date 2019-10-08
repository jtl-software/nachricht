<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/23
 */

namespace JTL\Nachricht\Transport\Amqp;

use JTL\Nachricht\Contract\Serializer\EventSerializer;
use JTL\Nachricht\Listener\ListenerProvider;

class AmqpTransportFactory
{
    /**
     * @param array $connectionSettings
     * @param EventSerializer $serializer
     * @param ListenerProvider $listenerProvider
     * @return AmqpTransport
     */
    public function createTransport(
        array $connectionSettings,
        EventSerializer $serializer,
        ListenerProvider $listenerProvider
    ): AmqpTransport {
        return new AmqpTransport(
            new AmqpConnectionSettings(
                $connectionSettings['host'],
                $connectionSettings['port'],
                $connectionSettings['user'],
                $connectionSettings['password'],
                $connectionSettings['vhost'] ?? '/'
            ),
            $serializer,
            $listenerProvider
        );
    }
}
