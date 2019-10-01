<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/10/01
 */

namespace JTL\Nachricht\Event\Cache;

use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class EventCacheTest
 * @package JTL\Nachricht\Event\Cache
 *
 * @covers \JTL\Nachricht\Event\Cache\EventCache
 */
class EventCacheTest extends TestCase
{
    /**
     * @var array
     */
    private $cacheData;

    /**
     * @var EventCache
     */
    private $eventCache;

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function setUp(): void
    {
        $this->cacheData = [
            'TestEvent' => [
                'routingKey' => 'msg__test_queue',
                'listenerList' => [
                    [
                        'listenerClass' => 'TestListener',
                        'method' => 'listen',
                    ],
                ],
            ],
        ];

        $this->eventCache = new EventCache($this->cacheData);
    }

    public function testGetListenerListForEvent(): void
    {
        $this->assertSame(
            $this->cacheData['TestEvent']['listenerList'],
            $this->eventCache->getListenerListForEvent('TestEvent')
        );
    }

    public function testGetRoutingKeyForEvent(): void
    {
        $this->assertSame(
            $this->cacheData['TestEvent']['routingKey'],
            $this->eventCache->getRoutingKeyForEvent('TestEvent')
        );
    }

    public function testGetEventClassList(): void
    {
        $this->assertSame(array_keys($this->cacheData), $this->eventCache->getEventClassList());
    }
}
