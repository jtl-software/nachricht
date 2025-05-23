<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Serializer;

use JTL\Nachricht\Message\AbstractAmqpTransportableMessage;
use JTL\Nachricht\Serializer\Exception\DeserializationFailedException;
use PHPUnit\Framework\TestCase;

class StubAmqpMessage extends AbstractAmqpTransportableMessage
{
}

/**
 * Class PhpMessageSerializerTest
 * @package JTL\Nachricht\Serializer
 *
 * @covers \JTL\Nachricht\Serializer\PhpMessageSerializer
 */
class PhpMessageSerializerTest extends TestCase
{
    private PhpMessageSerializer $serializer;
    private StubAmqpMessage $message;

    public function setUp(): void
    {
        $this->message = new StubAmqpMessage();
        $this->serializer = new PhpMessageSerializer();
    }

    public function testCanSerializeAndDeserialize(): void
    {
        $message = new StubAmqpMessage();
        $serializedMessage = $this->serializer->serialize($message);
        $deserializedMessage = $this->serializer->deserialize($serializedMessage);

        $this->assertIsString($serializedMessage);
        $this->assertInstanceOf(StubAmqpMessage::class, $deserializedMessage);
    }

    public function testCanNotDeserializeBecauseStringIsInvalid(): void
    {
        $this->expectException(DeserializationFailedException::class);
        $this->serializer->deserialize('fooO:38:"JTL\Nachricht\Serializer\MockAmqpMessage":0:{}');
    }
}
