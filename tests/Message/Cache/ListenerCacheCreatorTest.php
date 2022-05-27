<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/09/12
 */

namespace JTL\Nachricht\Message\Cache;

use PHPUnit\Framework\TestCase;

/**
 * @covers \JTL\Nachricht\Message\Cache\MessageCacheCreator
 */
class ListenerCacheCreatorTest extends TestCase
{
    private string $cacheFile;
    private array $lookupPathList;

    public function setUp(): void
    {
        $this->cacheFile = __DIR__ . '/Fixtures/test.cache';
        $this->lookupPathList = [__DIR__ . '/Fixtures/MessageCacheLookupPath'];
    }

    public function tearDown(): void
    {
        @unlink($this->cacheFile);
    }

    public function testWriteCacheFile(): void
    {
        $sut = new MessageCacheCreator();
        $sut->create($this->cacheFile, $this->lookupPathList, true, []);
        $this->assertFileExists($this->cacheFile);
    }

    public function testCanCreateMessageCache(): void
    {
        $sut = new MessageCacheCreator();
        $cache = $sut->create($this->cacheFile, $this->lookupPathList, true, []);
        $this->assertCount(1, $cache->getMessageClassList());
        $this->assertEquals(
            'JTL\Nachricht\Message\Cache\Fixtures\MessageCacheLookupPath\Foo',
            $cache->getMessageClassList()[0]
        );
    }
}
