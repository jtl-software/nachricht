<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/23
 */

namespace JTL\Nachricht\Transport\Amqp;

use InvalidArgumentException;
use JTL\Nachricht\Contract\Serializer\MessageSerializer;
use JTL\Nachricht\Listener\ListenerProvider;
use Psr\Log\LoggerInterface;

class AmqpTransportFactory
{
    public function __construct(private readonly AmqpConnectionFactory $connectionFactory)
    {
    }

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
                $this->getFromSettingsArray('host', $connectionSettings),
                (int)$this->getFromSettingsArray('port', $connectionSettings),
                $this->getFromSettingsArray('httpPort', $connectionSettings),
                $this->getFromSettingsArray('user', $connectionSettings),
                $this->getFromSettingsArray('password', $connectionSettings),
                $this->getFromSettingsArray('vhost', $connectionSettings, '/'),
                (float)$this->getFromSettingsArray('timeout', $connectionSettings, '3.0'),
            ),
            $this->connectionFactory,
            $serializer,
            $listenerProvider,
            $logger
        );
    }

    /**
     * @param array<string, string> $settings
     * @throws InvalidArgumentException
     */
    private function getFromSettingsArray(
        string $key,
        array $settings,
        string $default = null
    ): string {
        return $settings[$key] ?? $default ?? throw new \InvalidArgumentException("Missing {$key} in amqp connectionSettings");
    }
}
