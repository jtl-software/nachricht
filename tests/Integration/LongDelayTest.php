<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\LongDelayTestMessage;
use JTL\Nachricht\Transport\Amqp\AmqpTransport;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * EA-8268 asks for delays "in the range of minutes to hours" to be covered. Waiting them out is
 * obviously not an option in CI, so this asserts the two properties that can be checked in
 * seconds: the broker accepts a long delay without complaining, and the message is held inside
 * the delayed exchange rather than sitting in the queue or being delivered early.
 */
#[TestDox('Long delays (minutes to hours)')]
final class LongDelayTest extends IntegrationTestCase
{
    private const ONE_HOUR = 3600;

    #[TestDox('a one-hour delay is accepted, held outside the queue and not delivered early')]
    public function testOneHourDelayIsHeldAndNotDeliveredEarly(): void
    {
        $routingKey = LongDelayTestMessage::getRoutingKey();
        $queueName = AmqpTransport::MESSAGE_QUEUE_PREFIX . $routingKey;
        $this->purgeQueuesFor($routingKey);

        $transport = $this->createTransport([LongDelayTestMessage::class]);

        /** @var array<int, string> $received */
        $received = [];
        $this->subscribe(
            $transport,
            $this->subscriptionFor($routingKey),
            function (LongDelayTestMessage $message) use (&$received): void {
                $received[] = $message->getPayload();
            },
        );

        $transport->publish(
            new LongDelayTestMessage(payload: 'in-one-hour', delay: self::ONE_HOUR),
            self::ONE_HOUR,
        );

        $this->pollFor($transport, 6.0);

        self::assertSame([], $received, 'a message scheduled an hour out was delivered immediately');

        $this->assertQueueCountersEventually(
            $queueName,
            0,
            0,
            'the message should be held by the delayed exchange, not parked in the queue',
        );
    }
}
