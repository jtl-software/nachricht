<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Transport;

use PHPUnit\Framework\Attributes\CoversClass;
use JTL\Generic\StringCollection;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class SubscriptionSettingsTest
 * @package JTL\Nachricht\Transport
 */
#[CoversClass(SubscriptionSettings::class)]
class SubscriptionSettingsTest extends TestCase
{
    public function testCreate(): void
    {
        $stringCollection = new StringCollection();
        $ttl = random_int(1, 99);
        $settings = new SubscriptionSettings($stringCollection, $ttl);

        $this->assertSame($stringCollection, $settings->getQueueNameList());
        $this->assertSame($ttl, $settings->getTtl());
    }
}
