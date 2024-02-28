<?php

declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Message;

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
class AbstractAmqpTransportableMessageTest extends TestCase
{
    public function testCanCreateWithMessageId(): void
    {
        $messageId = uniqid();
        $message = new TestAmqpMessage($messageId);
        $this->assertEquals($messageId, $message->getMessageId());
    }

    public function testCanCreateWithoutMessageId(): void
    {
        $message = new TestAmqpMessage();
        $this->assertIsString($message->getMessageId());
        $this->assertTrue(strlen($message->getMessageId()) > 0);
    }

    public function testCanGetAndSetLastErrorMessage(): void
    {
        $errorMessage = uniqid();
        $message = new TestAmqpMessage();
        self::assertNull($message->getLastErrorMessage());
        $message->setLastError($errorMessage);

        $this->assertSame($errorMessage, $message->getLastErrorMessage());
    }

    public function testDefaultErrorMessageIsNull(): void
    {
        $message = new TestAmqpMessage();
        self::assertNull($message->getLastErrorMessage());
    }


    public function testCanCheckIfMessageIsDeadLetterTrue(): void
    {
        $message = new class extends AbstractAmqpTransportableMessage {
            const DEFAULT_RETRY_COUNT = 0;
        };
        $this->assertTrue($message->isDeadLetter());
    }

    public function testCanCheckIfMessageIsDeadLetterFalse(): void
    {
        $message = new class extends AbstractAmqpTransportableMessage {
            const DEFAULT_RETRY_COUNT = 1;
        };
        $this->assertFalse($message->isDeadLetter());
    }

    public function testCanIncreaseReceiveCountOnDeserialization(): void
    {
        $message = new TestAmqpMessage();
        $this->assertFalse($message->isDeadLetter());
        $message = unserialize(serialize($message));
        $this->assertTrue($message->isDeadLetter());
    }

    public function testGetRoutingKey(): void
    {
        $message = new TestAmqpMessage();
        $this->assertEquals('JTL_Nachricht_Message_TestAmqpMessage', $message->getRoutingKey());
    }

    public function testCanSetReceiveCount(): void
    {
        $receiveCount = random_int(1, 10000);
        $message = new TestAmqpMessage();
        $this->assertEquals(0, $message->getReceiveCount());
        $message->setReceiveCount($receiveCount);
        $this->assertEquals($receiveCount, $message->getReceiveCount());
    }

    public function testCanBeCreatedWithCreatedAt(): void
    {
        $createdAt = new \DateTimeImmutable();
        $sut = new TestAmqpMessage(uniqid(), $createdAt);

        self::assertSame($createdAt, $sut->getCreatedAt());
    }

    public function testCanBeCreatedWithoutCreatedAt(): void
    {
        $before = new \DateTimeImmutable();
        $sut = new TestAmqpMessage(uniqid());
        $after = new \DateTimeImmutable();

        self::assertTrue($sut->getCreatedAt() >= $before && $sut->getCreatedAt() <= $after);
    }

    public function testCanGetRetryCount(): void
    {
        $sut = new TestAmqpMessage(uniqid());
        self::assertSame(TestAmqpMessage::DEFAULT_RETRY_COUNT, $sut->getRetryCount());
    }

    public function testCanGetRetryDelay(): void
    {
        $sut = new TestAmqpMessage(uniqid());
        self::assertSame(TestAmqpMessage::RETRY_DELAY, $sut->getRetryDelay());
    }
}
