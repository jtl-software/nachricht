<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Serializer;

use JTL\Nachricht\Collection\StringCollection;
use JTL\Nachricht\Event\AbstractAmqpEvent;
use JTL\Nachricht\Serializer\Exception\DeserializationFailedException;
use PHPUnit\Framework\TestCase;

class MockAmqpEvent extends AbstractAmqpEvent
{

    /**
     * @return StringCollection
     */
    public function getListenerClassList(): StringCollection
    {
        return new StringCollection();
    }
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
     * @var MockAmqpEvent
     */
    private $event;

    public function setUp(): void
    {
        $this->event = new MockAmqpEvent();
        $this->serializer = new PhpEventSerializer();
    }

    public function testCanSerialize(): void
    {
        $this->assertEquals(
            'O:38:"JTL\Nachricht\Serializer\MockAmqpEvent":0:{}',
            $this->serializer->serialize($this->event)
        );
    }

    public function testCanDeserialize(): void
    {
        $event = $this->serializer->deserialize('O:38:"JTL\Nachricht\Serializer\MockAmqpEvent":0:{}');
        $this->assertInstanceOf(MockAmqpEvent::class, $event);
    }
    
    public function testCanNotDeserializeBecauseStringIsInvalid(): void
    {
        $this->expectException(DeserializationFailedException::class);
        $this->serializer->deserialize('fooO:38:"JTL\Nachricht\Serializer\MockAmqpEvent":0:{}');
    }
}
