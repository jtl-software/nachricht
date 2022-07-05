<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Examples\Amqp\Message;


use JTL\Nachricht\Message\AbstractAmqpTransportableMessage;

class DummyAmqpMessage extends AbstractAmqpTransportableMessage
{
    /**
     * @var string
     */
    private $data;

    public function __construct(string $data, int $delay = 0)
    {
        parent::__construct(delay: $delay);
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
