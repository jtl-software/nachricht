<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Message;

use JTL\Nachricht\Message\Cache\TestMessage;
use PHPUnit\Framework\TestCase;

class TestAmqpMessage extends AbstractAmqpTransportableMessage
{
    const DEFAULT_RETRY_COUNT = 1;
}

/**
 * Class AbstractMessageTest
 * @package JTL\Nachricht\Message
 *
 * @covers \JTL\Nachricht\Message\AbstractAmqpTransportableMessage
 */
class AbstractMessageTest extends TestCase
{
    public function testCanCreateWithMessageId()
    {
        $eventId = uniqid();
        $event = new TestAmqpMessage($eventId);
        $this->assertEquals($eventId, $event->getMessageId());
    }

    public function testCanCreateWithoutMessageId()
    {
        $event = new TestAmqpMessage();
        $this->assertIsString($event->getMessageId());
        $this->assertTrue(strlen($event->getMessageId()) > 0);
    }

    public function testCanSetLastErrorMessage()
    {
        $errorMessage = uniqid();
        $event = new TestAmqpMessage();
        $event->setLastError($errorMessage);

        $this->assertStringContainsString($errorMessage, serialize($event));
    }

    public function testCanCheckIfMessageIsDeadLetterTrue()
    {
        $event = new class extends AbstractAmqpTransportableMessage {
            const DEFAULT_RETRY_COUNT = 0;
        };
        $this->assertTrue($event->isDeadLetter());
    }

    public function testCanCheckIfMessageIsDeadLetterFalse()
    {
        $event = new class extends AbstractAmqpTransportableMessage {
            const DEFAULT_RETRY_COUNT = 1;
        };
        $this->assertFalse($event->isDeadLetter());
    }

    public function testCanIncreaseReceiveCountOnDeserialization()
    {
        $event = new TestAmqpMessage();
        $this->assertFalse($event->isDeadLetter());
        $event = unserialize(serialize($event));
        $this->assertTrue($event->isDeadLetter());
    }

    public function testGetRoutingKey(): void
    {
        $event = new TestAmqpMessage();
        $this->assertEquals(get_class($event), $event->getRoutingKey());
    }

    public function testGetExchange(): void
    {
        $event = new TestAmqpMessage();
        $this->assertEquals('', $event->getExchange());
    }
}
