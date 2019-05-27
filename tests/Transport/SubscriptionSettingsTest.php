<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Transport;

use JTL\Nachricht\Collection\StringCollection;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Class SubscriptionSettingsTest
 * @package JTL\Nachricht\Transport
 *
 * @covers \JTL\Nachricht\Transport\SubscriptionSettings
 */
class SubscriptionSettingsTest extends TestCase
{
    public function testCreate(): void
    {
        $stringCollection = Mockery::mock(StringCollection::class);
        $settings = new SubscriptionSettings($stringCollection);

        $this->assertEquals($stringCollection, $settings->getQueueNameList());
    }
}
