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
        $messageId = uniqid();
        $message = new TestAmqpMessage($messageId);
        $this->assertEquals($messageId, $message->getMessageId());
    }

    public function testCanCreateWithoutMessageId()
    {
        $message = new TestAmqpMessage();
        $this->assertIsString($message->getMessageId());
        $this->assertTrue(strlen($message->getMessageId()) > 0);
    }

    public function testCanGetAndSetLastErrorMessage()
    {
        $errorMessage = uniqid();
        $message = new TestAmqpMessage();
        $message->setLastError($errorMessage);

        $this->assertSame($errorMessage, $message->getLastErrorMessage());
    }

    public function testCanCheckIfMessageIsDeadLetterTrue()
    {
        $message = new class extends AbstractAmqpTransportableMessage {
            const DEFAULT_RETRY_COUNT = 0;
        };
        $this->assertTrue($message->isDeadLetter());
    }

    public function testCanCheckIfMessageIsDeadLetterFalse()
    {
        $message = new class extends AbstractAmqpTransportableMessage {
            const DEFAULT_RETRY_COUNT = 1;
        };
        $this->assertFalse($message->isDeadLetter());
    }

    public function testCanIncreaseReceiveCountOnDeserialization()
    {
        $message = new TestAmqpMessage();
        $this->assertFalse($message->isDeadLetter());
        $message = unserialize(serialize($message));
        $this->assertTrue($message->isDeadLetter());
    }

    public function testGetRoutingKey(): void
    {
        $message = new TestAmqpMessage();
        $this->assertEquals(get_class($message), $message->getRoutingKey());
    }

    public function testGetExchange(): void
    {
        $message = new TestAmqpMessage();
        $this->assertEquals('', $message->getExchange());
    }

    public function testCanSetReceiveCount(): void
    {
        $receiveCount = random_int(1, 10000);
        $message = new TestAmqpMessage();
        $this->assertEquals(0, $message->getReceiveCount());
        $message->setReceiveCount($receiveCount);
        $this->assertEquals($receiveCount, $message->getReceiveCount());
    }
}
