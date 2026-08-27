<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Nachricht\Integration\Fixtures\ComplexPayloadMessage;
use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * EA-8268: the delayed exchange fork stores message bodies in Leveled (on disk) instead of
 * Mnesia, so payload integrity through the delayed path is worth asserting on something wider
 * than a short ASCII string - a truncation or encoding bug would not show up otherwise.
 */
#[TestDox('Payload integrity through the broker')]
final class PayloadIntegrityTest extends IntegrationTestCase
{
    /**
     * @return iterable<string, array{int}>
     */
    public static function delayProvider(): iterable
    {
        yield 'undelayed' => [0];
        yield 'through the delayed exchange' => [2];
    }

    #[DataProvider('delayProvider')]
    #[TestDox('a large unicode/binary payload with nested data survives intact ($delaySeconds s delay)')]
    public function testComplexPayloadSurvivesTheRoundTrip(int $delaySeconds): void
    {
        $routingKey = ComplexPayloadMessage::getRoutingKey();
        $this->purgeQueuesFor($routingKey);

        $blob = str_repeat("Grüße 🐇 \x00\x01\x02 ünïcödé ", 8192);
        $structure = [
            'nested' => ['list' => [1, 2, 3], 'map' => ['a' => 1.5, 'b' => null, 'c' => false]],
            'unicode key äöü' => "line1\nline2\ttabbed",
            'large_int' => PHP_INT_MAX,
        ];

        $transport = $this->createTransport([ComplexPayloadMessage::class]);

        /** @var array<int, ComplexPayloadMessage> $received */
        $received = [];
        $this->subscribe(
            $transport,
            $this->subscriptionFor($routingKey),
            function (ComplexPayloadMessage $message) use (&$received): void {
                $received[] = $message;
            },
        );

        $transport->publish(new ComplexPayloadMessage($blob, $structure, delay: $delaySeconds), $delaySeconds);

        $this->pollFor($transport, $delaySeconds + 6.0);

        self::assertCount(1, $received);
        self::assertSame(strlen($blob), strlen($received[0]->getBlob()), 'payload was truncated');
        self::assertSame($blob, $received[0]->getBlob(), 'payload was altered in transit');
        self::assertSame($structure, $received[0]->getStructure(), 'nested structure did not survive intact');
    }
}
