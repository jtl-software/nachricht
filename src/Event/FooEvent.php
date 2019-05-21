<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/20
 */

namespace JTL\Nachricht\Event;


use JTL\Nachricht\Collection\StringCollection;
use JTL\Nachricht\Contracts\Event\AmqpEvent;
use JTL\Nachricht\Contracts\Event\Event;
use JTL\Nachricht\Listener\FooListener;

class FooEvent implements AmqpEvent
{
    private $fooProperty;

    /**
     * FooEvent constructor.
     * @param $fooProperty
     */
    public function __construct($fooProperty)
    {
        $this->fooProperty = $fooProperty;
    }

    public function getListenerClassList(): StringCollection
    {
        $col = new StringCollection();
        $col[] = FooListener::class;
        return $col;
    }

    public function serialize(): string
    {
        return self::class . '|' . json_encode([
            'foo' => $this->fooProperty
        ]);
    }

    public static function deserialize(string $data): Event
    {
        return new self(json_decode($data, true)['foo']);
    }

    public function getRoutingKey(): string
    {
        return 'foo_queue';
    }

    public function getExchange(): string
    {
        return '';
    }
}