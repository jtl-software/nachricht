<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Examples\Amqp\Event;


use JTL\Nachricht\Event\AbstractAmqpEvent;

class DummyAmqpEvent extends AbstractAmqpEvent
{
    /**
     * @var string
     */
    private $data;

    public function __construct(string $data)
    {
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
