<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/10/01
 */

namespace JTL\Nachricht\Message\Cache;

use JTL\Nachricht\Message\AbstractAmqpTransportableMessage;
use Mockery;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\TestCase;

/**
 * Class AmqpMessageRoutingKeyExtractorTest
 * @package JTL\Nachricht\Message\Cache
 *
 * @covers \JTL\Nachricht\Message\Cache\AmqpMessageRoutingKeyExtractor
 */
class AmqpMessageRoutingKeyExtractorTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testCanGetMessageData()
    {
        $class = Mockery::mock(Class_::class);
        $className = Mockery::mock(Name::class);
        $className->parts = ['JTL', 'Nachricht', 'Message', 'Cache', 'TestAmqpMessage'];
        $class->namespacedName = $className;

        $class->shouldReceive('isAbstract')
            ->once()
            ->andReturnFalse();

        $extractor = new AmqpMessageRoutingKeyExtractor();

        $extractor->enterNode($class);

        $this->assertTrue($extractor->isClassMessage());
        $this->assertSame(TestAmqpMessage::class, $extractor->getMessageClass());
        $this->assertSame('JTL_Nachricht_Message_Cache_TestAmqpMessage', $extractor->getRoutingKey());
    }
}

class TestAmqpMessage extends AbstractAmqpTransportableMessage
{
}
