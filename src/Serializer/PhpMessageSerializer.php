<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/17
 */

namespace JTL\Nachricht\Serializer;

use JTL\Nachricht\Contract\Message\Message;
use JTL\Nachricht\Contract\Serializer\MessageSerializer;
use JTL\Nachricht\Serializer\Exception\DeserializationFailedException;

class PhpMessageSerializer implements MessageSerializer
{
    /**
     * @param object $event
     * @return string
     */
    public function serialize(object $event): string
    {
        return serialize($event);
    }

    /**
     * @param string $serializedMessage
     * @return Message
     * @throws DeserializationFailedException
     */
    public function deserialize(string $serializedMessage): object
    {
        $result = @unserialize($serializedMessage);

        if ($result === false || !$result instanceof Message) {
            throw new DeserializationFailedException();
        }

        return $result;
    }
}
