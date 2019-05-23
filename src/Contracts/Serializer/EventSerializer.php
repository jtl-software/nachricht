<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/17
 */

namespace JTL\Nachricht\Contracts\Serializer;

use JTL\Nachricht\Contracts\Event\Event;
use JTL\Nachricht\Serializer\Exception\DeserializationFailedException;

interface EventSerializer
{
    /**
     * @param Event $event
     * @return string
     */
    public function serialize(Event $event): string;

    /**
     * @param string $serializedEvent
     * @return Event
     * @throws DeserializationFailedException
     */
    public function deserialize(string $serializedEvent): Event;
}
