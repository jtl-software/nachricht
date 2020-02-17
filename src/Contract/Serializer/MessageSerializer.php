<?php
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/17
 */

namespace JTL\Nachricht\Contract\Serializer;

use JTL\Nachricht\Serializer\Exception\DeserializationFailedException;

interface MessageSerializer
{
    /**
     * @param object $event
     * @return string
     */
    public function serialize(object $event): string;

    /**
     * @param string $serializedMessage
     * @return object
     * @throws DeserializationFailedException
     */
    public function deserialize(string $serializedMessage): object;
}
