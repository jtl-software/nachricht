<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Examples\Amqp\Message;


use JTL\Nachricht\Message\AbstractAmqpTransportableMessage;

class DummyRetryDelayAmqpMessage extends AbstractAmqpTransportableMessage
{
    public function __construct(private string $data, int $retryDelay)
    {
        parent::__construct(null, null, self::ENQUEUE_DELAY, $retryDelay);
    }

    public function getData(): string
    {
        return $this->data;
    }
}
