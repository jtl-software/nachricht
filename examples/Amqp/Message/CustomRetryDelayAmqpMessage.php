<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Examples\Amqp\Message;


use JTL\Nachricht\Message\AbstractAmqpTransportableMessage;

class CustomRetryDelayAmqpMessage extends AbstractAmqpTransportableMessage
{
    public function __construct(private string $data)
    {
        parent::__construct();
    }

    public function getRetryDelay(): int
    {
        return random_int(3, 10);
    }

    public function getData(): string
    {
        return $this->data;
    }
}
