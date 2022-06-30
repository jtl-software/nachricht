<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Examples\Amqp\Message;


use JTL\Nachricht\Message\AbstractAmqpTransportableMessage;

class Dummy2AmqpMessage extends AbstractAmqpTransportableMessage
{
    public function getRetryDelay(): int
    {
        return 4;
    }

    private $data;

    public function __construct(string $data)
    {
        parent::__construct();
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }
}
