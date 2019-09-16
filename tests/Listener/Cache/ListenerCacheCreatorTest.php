<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/09/12
 */

namespace JTL\Nachricht\Listener\Cache;

use Mockery;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
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

    /**
     * @var ListenerCacheCreator
     */
    private $listenerCacheCreator;

    /**
     * @var string
     */
    private $cacheFile;

    /**
     * @var bool
     */
    private $isDevelopment;

    /**
     * @var array
     */
    private $lookupPathList;

    /**
     * @var Mockery\MockInterface|Parser
     */
    private $parser;

    /**
     * @var Mockery\MockInterface|Stmt
     */
    private $stmt;

    /**
     * @var Mockery\MockInterface
     */
    private $cacheFileLoader;

    public function setUp(): void
    {
        Mockery::getConfiguration()->setConstantsMap([
            ParserFactory::class => [
                'ONLY_PHP7' => 3
            ]
        ]);

        $this->configCache = Mockery::mock('overload:' . ConfigCache::class);
        $this->parserFactory = Mockery::mock('overload:' . ParserFactory::class);
        $this->nameResolver = Mockery::mock('overload:' . NameResolver::class);
        $this->listenerDetector = Mockery::mock('overload:' . ListenerDetector::class);
        $this->nodeTraverser = Mockery::mock('overload:' . NodeTraverser::class);
        $this->parser = Mockery::mock(Parser::class);
        $this->stmt = Mockery::mock(Stmt::class);
        $this->cacheFileLoader = Mockery::mock('overload:' . ListenerCacheFileLoader::class);

        $this->listenerCacheCreator = new ListenerCacheCreator();
        $this->cacheFile = uniqid('cachefile', true);
        $this->lookupPathList = [
            uniqid('path', true)
        ];
        $this->isDevelopment = (bool)random_int(0, 1);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testCanCreate()
    {
        global $globCallCount;
        $globCallCount = 0;

        $this->configCache->shouldReceive('isFresh')
            ->once()
            ->andReturnFalse();

        $this->parserFactory->shouldReceive('create')
            ->with(3)
            ->once()
            ->andReturn($this->parser);

        $this->nodeTraverser->shouldReceive('addVisitor')
            ->with(Mockery::type(NameResolver::class))
            ->once();

        $this->nodeTraverser->shouldReceive('addVisitor')
            ->with(Mockery::type(ListenerDetector::class))
            ->once();

        $this->listenerDetector->shouldReceive('isClassListener')
            ->once()
            ->andReturnTrue();

        $this->parser->shouldReceive('parse')
            ->once()
            ->with('DUMMYDATA')
            ->andReturn([$this->stmt]);

        $this->nodeTraverser->shouldReceive('traverse')
            ->with([$this->stmt])
            ->once();

        $this->listenerDetector->shouldReceive('getListenerClass')
            ->once()
            ->andReturn('FooListener');

        $this->listenerDetector->shouldReceive('getListenerMethods')
            ->once()
            ->andReturn([
                [
                    'methodName' => 'fooMethod',
                    'eventClass' => 'FooEvent'
                ]
            ]);

        $eventToListenerMap = [
            'FooEvent' => [
                [
                    'listenerClass' => 'FooListener',
                    'method' => 'fooMethod'
                ]
            ]
        ];

        $map = var_export($eventToListenerMap, true);

        $this->configCache->shouldReceive('write')
            ->with("<?php\nreturn {$map};")
            ->once();

        $this->cacheFileLoader->shouldReceive('load')
            ->with($this->cacheFile)
            ->once()
            ->andReturn($eventToListenerMap);

        $cache = $this->listenerCacheCreator->create($this->cacheFile, $this->lookupPathList, $this->isDevelopment);

        $this->assertEquals([
            [
                'listenerClass' => 'FooListener',
                'method' => 'fooMethod'
            ]
        ], $cache->getListenerListForEvent('FooEvent'));
    }
}

function glob($pattern)
{
    global $globCallCount;
    $globCallCount++;

    if ($globCallCount === 1) {
        return ['test.php'];
    }

    if ($globCallCount === 2) {
        return ['test'];
    }

    return [];
}

function file_get_contents($filename)
{
    return 'DUMMYDATA';
}
