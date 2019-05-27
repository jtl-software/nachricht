<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/05/27
 */

namespace JTL\Nachricht\Collection;

use PHPUnit\Framework\TestCase;

/**
 * Class StringCollectionTest
 * @package JTL\Nachricht\Collection
 *
 * @covers \JTL\Nachricht\Collection\StringCollection
 */
class StringCollectionTest extends TestCase
{
    public function testCanBeCreated(): void
    {
        $collection = new StringCollection();
        $this->assertInstanceOf(StringCollection::class, $collection);
    }
}
