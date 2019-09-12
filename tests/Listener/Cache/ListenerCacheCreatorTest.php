<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/09/12
 */

namespace JTL\Nachricht\Listener\Cache;

use Mockery;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\ConfigCache;

/**
 * Class ListenerCacheCreatorTest
 * @package JTL\Nachricht\Listener\Cache
 *
 * @covers \JTL\Nachricht\Listener\Cache\ListenerCacheCreator
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ListenerCacheCreatorTest extends TestCase
{
    /**
     * @var Mockery\MockInterface
     */
    private $configCache;

    /**
     * @var Mockery\MockInterface
     */
    private $parserFactory;

    /**
     * @var Mockery\MockInterface
     */
    private $nameResolver;

    /**
     * @var Mockery\MockInterface
     */
    private $listenerDetector;

    /**
     * @var Mockery\MockInterface
     */
    private $nodeTraverser;

    public function setUp(): void
    {
        $this->configCache = Mockery::mock('overload:' . ConfigCache::class);
        $this->parserFactory = Mockery::mock('overload:' . ParserFactory::class);
        $this->nameResolver = Mockery::mock('overload:' . NameResolver::class);
        $this->listenerDetector = Mockery::mock('overload:' . ListenerDetector::class);
        $this->nodeTraverser = Mockery::mock('overload:' . NodeTraverser::class);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testCanCreate()
    {
    }
}
