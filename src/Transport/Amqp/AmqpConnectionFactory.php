<?php declare(strict_types=1);

namespace JTL\Nachricht\Transport\Amqp;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class AmqpConnectionFactory
{
    public function connect(AmqpConnectionSettings $connectionSettings): AMQPStreamConnection
    {
        return new AMQPStreamConnection(
            $connectionSettings->getHost(),
            $connectionSettings->getPort(),
            $connectionSettings->getUser(),
            $connectionSettings->getPassword(),
            $connectionSettings->getVhost(),
            connection_timeout: $connectionSettings->getTimeout(),
            read_write_timeout: $connectionSettings->getTimeout(),
            channel_rpc_timeout: $connectionSettings->getTimeout(),
        );
    }
}
