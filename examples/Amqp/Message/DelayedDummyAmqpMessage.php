<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Examples\Amqp\Message;


use JTL\Nachricht\Message\AbstractAmqpTransportableMessage;

class DelayedDummyAmqpMessage extends AbstractAmqpTransportableMessage
{
    public function getDelay(): int
    {
        return $this->delay;
    }

    public function __construct(private string $data, private int $delay)
    {
        parent::__construct();
    }

    public function getData(): string
    {
        return $this->data;
    }
}
