<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/17
 */

namespace JTL\Nachricht\Serializer;

use JTL\Nachricht\Contracts\Event\Event;
use JTL\Nachricht\Contracts\Serializer\EventSerializer;
use JTL\Nachricht\Serializer\Exception\DeserializationFailedException;

class PhpEventSerializer implements EventSerializer
{
    public function serialize(Event $event): string
    {
        return serialize($event);
    }

    public function deserialize(string $serializedEvent): Event
    {
        $result = unserialize($serializedEvent);

        if ($result === false || !$result instanceof Event) {
            throw new DeserializationFailedException();
        }

        return $result;
    }
}
