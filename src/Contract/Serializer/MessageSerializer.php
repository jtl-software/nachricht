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
     * @param object $message
     * @return string
     */
    public function serialize(object $message): string;

    /**
     * @param string $serializedMessage
     * @return object
     * @throws DeserializationFailedException
     */
    public function deserialize(string $serializedMessage): object;
}
