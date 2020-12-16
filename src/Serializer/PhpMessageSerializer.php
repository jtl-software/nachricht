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
     * @param object $message
     * @return string
     */
    public function serialize(object $message): string
    {
        return serialize($message);
    }

    /**
     * @param string $serializedMessage
     * @return Message
     * @throws DeserializationFailedException
     */
    public function deserialize(string $serializedMessage): object
    {
        try {
            $result = @unserialize($serializedMessage);
        } catch (\Throwable $throwable) {
            throw new DeserializationFailedException($throwable->getMessage());
        }

        if ($result === false || !$result instanceof Message) {
            throw new DeserializationFailedException();
        }

        return $result;
    }
}
