<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Examples\Amqp\Event;


use JTL\Nachricht\Event\AbstractAmqpEvent;

class CreateFileAmqpEvent extends AbstractAmqpEvent
{
    /**
     * @var string
     */
    private $filename;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public static function getRoutingKey(): string
    {
        return 'test_queue';
    }
}
