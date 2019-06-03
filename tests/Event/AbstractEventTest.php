<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Event;

use JTL\Nachricht\Collection\StringCollection;
use PHPUnit\Framework\TestCase;

class TestAmqpEvent extends AbstractAmqpEvent
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
 * Class AbstractEventTest
 * @package JTL\Nachricht\Event
 *
 * @covers \JTL\Nachricht\Event\AbstractAmqpEvent
 */
class AbstractEventTest extends TestCase
{
    /**
     * @var TestAmqpEvent
     */
    private $testEvent;

    public function setUp(): void
    {
        $this->testEvent = new TestAmqpEvent();
    }

    public function testGetRoutingKey(): void
    {
        $this->assertEquals(get_class($this->testEvent), $this->testEvent->getRoutingKey());
    }

    public function testGetExchange(): void
    {
        $this->assertEquals('', $this->testEvent->getExchange());
    }

    public function testGetMaxRetryCount(): void
    {
        $this->assertEquals(3, $this->testEvent->getMaxRetryCount());
    }
}
