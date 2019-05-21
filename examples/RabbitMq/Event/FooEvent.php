<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Examples\RabbitMq\Event;


use JTL\Nachricht\Collection\StringCollection;
use JTL\Nachricht\Contracts\Event\AmqpEvent;
use JTL\Nachricht\Examples\RabbitMq\Listener\FooListener;

class FooEvent implements AmqpEvent
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

    public function getRoutingKey(): string
    {
        return 'test_queue';
    }

    public function getExchange(): string
    {
        return '';
    }

    public function getListenerClassList(): StringCollection
    {
        return StringCollection::from(FooListener::class);
    }
}