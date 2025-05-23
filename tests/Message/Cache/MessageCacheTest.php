<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/10/01
 */

namespace JTL\Nachricht\Message\Cache;

use PHPUnit\Framework\TestCase;

/**
 * Class MessageCacheTest
 * @package JTL\Nachricht\Message\Cache
 *
 * @covers \JTL\Nachricht\Message\Cache\MessageCache
 */
class MessageCacheTest extends TestCase
{
    private array $cacheData;
    private MessageCache $messageCache;


    public function setUp(): void
    {
        $this->cacheData = [
            'TestMessage' => [
                'routingKey' => 'msg__test_queue',
                'listenerList' => [
                    [
                        'listenerClass' => 'TestListener',
                        'method' => 'listen',
                    ],
                ],
            ],
        ];

        $this->messageCache = new MessageCache($this->cacheData);
    }

    public function testGetListenerListForMessage(): void
    {
        $this->assertSame(
            $this->cacheData['TestMessage']['listenerList'],
            $this->messageCache->getListenerListForMessage('TestMessage')
        );
    }

    public function testGetRoutingKeyForMessage(): void
    {
        $this->assertSame(
            $this->cacheData['TestMessage']['routingKey'],
            $this->messageCache->getRoutingKeyForMessage('TestMessage')
        );
    }

    public function testGetMessageClassList(): void
    {
        $this->assertSame(array_keys($this->cacheData), $this->messageCache->getMessageClassList());
    }
}
