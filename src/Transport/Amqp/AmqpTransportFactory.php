<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/23
 */

namespace JTL\Nachricht\Transport\Amqp;

use JTL\Nachricht\Contract\Serializer\MessageSerializer;
use JTL\Nachricht\Listener\ListenerProvider;
use Psr\Log\LoggerInterface;

class AmqpTransportFactory
{
    /**
     * @param array<string, string> $connectionSettings
     * @param MessageSerializer $serializer
     * @param ListenerProvider $listenerProvider
     * @param LoggerInterface|null $logger
     * @return AmqpTransport
     */
    public function createTransport(
        array $connectionSettings,
        MessageSerializer $serializer,
        ListenerProvider $listenerProvider,
        LoggerInterface $logger = null
    ): AmqpTransport {
        return new AmqpTransport(
            new AmqpConnectionSettings(
                $connectionSettings['host'],
                (int)$connectionSettings['port'],
                $connectionSettings['httpPort'],
                $connectionSettings['user'],
                $connectionSettings['password'],
                $connectionSettings['vhost'] ?? '/'
            ),
            $serializer,
            $listenerProvider,
            $logger
        );
    }
}
