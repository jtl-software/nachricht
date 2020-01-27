<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Event;

use JTL\Nachricht\Event\Cache\TestEvent;
use PHPUnit\Framework\TestCase;

class TestAmqpEvent extends AbstractAmqpEvent
{
    const DEFAULT_RETRY_COUNT = 1;
}

/**
 * Class AbstractEventTest
 * @package JTL\Nachricht\Event
 *
 * @covers \JTL\Nachricht\Event\AbstractAmqpEvent
 */
class AbstractEventTest extends TestCase
{
    public function testCanCreateWithEventId()
    {
        $eventId = uniqid();
        $event = new TestAmqpEvent($eventId);
        $this->assertEquals($eventId, $event->getEventId());
    }

    public function testCanCreateWithoutEventId()
    {
        $event = new TestAmqpEvent();
        $this->assertIsString($event->getEventId());
        $this->assertTrue(strlen($event->getEventId()) > 0);
    }

    public function testCanSetLastErrorMessage()
    {
        $errorMessage = uniqid();
        $event = new TestAmqpEvent();
        $event->setLastError($errorMessage);

        $this->assertStringContainsString($errorMessage, serialize($event));
    }

    public function testCanCheckIfEventIsDeadLetterTrue()
    {
        $event = new class extends AbstractAmqpEvent {
            const DEFAULT_RETRY_COUNT = 0;
        };
        $this->assertTrue($event->isDeadLetter());
    }

    public function testCanCheckIfEventIsDeadLetterFalse()
    {
        $event = new class extends AbstractAmqpEvent {
            const DEFAULT_RETRY_COUNT = 1;
        };
        $this->assertFalse($event->isDeadLetter());
    }

    public function testCanIncreaseReceiveCountOnDeserialization()
    {
        $event = new TestAmqpEvent();
        $this->assertFalse($event->isDeadLetter());
        $event = unserialize(serialize($event));
        $this->assertTrue($event->isDeadLetter());
    }

    public function testGetRoutingKey(): void
    {
        $event = new TestAmqpEvent();
        $this->assertEquals(get_class($event), $event->getRoutingKey());
    }

    public function testGetExchange(): void
    {
        $event = new TestAmqpEvent();
        $this->assertEquals('', $event->getExchange());
    }
}
