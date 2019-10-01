<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/10/01
 */

namespace JTL\Nachricht\Event\Cache;

use JTL\Nachricht\Event\AbstractAmqpEvent;
use Mockery;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\TestCase;

/**
 * Class AmqpEventRoutingKeyExtractorTest
 * @package JTL\Nachricht\Event\Cache
 *
 * @covers \JTL\Nachricht\Event\Cache\AmqpEventRoutingKeyExtractor
 */
class AmqpEventRoutingKeyExtractorTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testCanGetEventData()
    {
        $class = Mockery::mock(Class_::class);
        $className = Mockery::mock(Name::class);
        $className->parts = ['JTL', 'Nachricht', 'Event', 'Cache', 'TestAmqpEvent'];
        $class->namespacedName = $className;

        $extractor = new AmqpEventRoutingKeyExtractor();

        $extractor->enterNode($class);

        $this->assertTrue($extractor->isClassEvent());
        $this->assertSame(TestAmqpEvent::class, $extractor->getEventClass());
        $this->assertSame(TestAmqpEvent::class, $extractor->getRoutingKey());
    }
}

class TestAmqpEvent extends AbstractAmqpEvent
{
}
