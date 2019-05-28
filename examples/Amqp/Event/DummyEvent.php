<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Examples\Amqp\Event;


use JTL\Nachricht\Collection\StringCollection;
use JTL\Nachricht\Event\AbstractEvent;
use JTL\Nachricht\Examples\Amqp\Listener\DummyListener;

class DummyEvent extends AbstractEvent
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

    /**
     * @return StringCollection
     */
    public function getListenerClassList(): StringCollection
    {
        return StringCollection::from(DummyListener::class);
    }
}