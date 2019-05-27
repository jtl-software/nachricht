<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Serializer;

use JTL\Nachricht\Collection\StringCollection;
use JTL\Nachricht\Event\AbstractEvent;
use JTL\Nachricht\Serializer\Exception\DeserializationFailedException;
use PHPUnit\Framework\TestCase;

class MockEvent extends AbstractEvent
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
     * @var MockEvent
     */
    private $event;

    public function setUp(): void
    {
        $this->event = new MockEvent();
        $this->serializer = new PhpEventSerializer();
    }

    public function testCanSerialize(): void
    {
        $this->assertEquals(
            'O:34:"JTL\Nachricht\Serializer\MockEvent":0:{}',
            $this->serializer->serialize($this->event)
        );
    }

    public function testCanDeserialize(): void
    {
        $event = $this->serializer->deserialize('O:34:"JTL\Nachricht\Serializer\MockEvent":0:{}');
        $this->assertInstanceOf(MockEvent::class, $event);
    }
    
    public function testCanNotDeserializeBecauseStringIsInvalid(): void
    {
        $this->expectException(DeserializationFailedException::class);
        $this->serializer->deserialize('fooO:34:"JTL\Nachricht\Serializer\MockEvent":0:{}');
    }
}
