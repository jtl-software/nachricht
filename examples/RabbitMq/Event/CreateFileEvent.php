<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/21
 */

namespace JTL\Nachricht\Examples\RabbitMq\Event;


use JTL\Nachricht\Collection\StringCollection;
use JTL\Nachricht\Event\AbstractEvent;
use JTL\Nachricht\Examples\RabbitMq\Listener\CreateFileListener;

class CreateFileEvent extends AbstractEvent
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
    public function getRoutingKey(): string
    {
        return 'test_queue';
    }

    /**
     * @return StringCollection
     */
    public function getListenerClassList(): StringCollection
    {
        return StringCollection::from(CreateFileListener::class);
    }
}