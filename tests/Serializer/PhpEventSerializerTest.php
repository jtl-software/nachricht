<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Serializer;

use JTL\Nachricht\Event\AbstractAmqpEvent;
use JTL\Nachricht\Serializer\Exception\DeserializationFailedException;
use PHPUnit\Framework\TestCase;

class StubAmqpEvent extends AbstractAmqpEvent
{
}

/**
 * Class PhpEventSerializerTest
 * @package JTL\Nachricht\Serializer
 *
 * @covers \JTL\Nachricht\Serializer\PhpEventSerializer
 */
class PhpEventSerializerTest extends TestCase
{
    /**
     * @var PhpEventSerializer
     */
    private $serializer;

    /**
     * @var StubAmqpEvent
     */
    private $event;

    public function setUp(): void
    {
        $this->event = new StubAmqpEvent();
        $this->serializer = new PhpEventSerializer();
    }

    public function testCanSerializeAndDeserialize(): void
    {
        $event = new StubAmqpEvent();
        $serializedEvent = $this->serializer->serialize($event);
        $deserializedEvent = $this->serializer->deserialize($serializedEvent);

        $this->assertIsString($serializedEvent);
        $this->assertInstanceOf(StubAmqpEvent::class, $deserializedEvent);
    }

    public function testCanNotDeserializeBecauseStringIsInvalid(): void
    {
        $this->expectException(DeserializationFailedException::class);
        $this->serializer->deserialize('fooO:38:"JTL\Nachricht\Serializer\MockAmqpEvent":0:{}');
    }
}
