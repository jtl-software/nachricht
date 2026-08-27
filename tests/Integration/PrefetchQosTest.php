<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\IntegrationTestMessage;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * EA-8268 flags that RabbitMQ denies global QoS by default from 4.3.3 on, and that the STAGE
 * broker logged refused global-QoS attempts during the upgrade reboots. AmqpTransport requests
 * per-consumer prefetch (basic_qos(0, 1, false) - the third argument is the global flag), which
 * remains permitted, so subscribing must not raise a channel error on 4.3.3.
 *
 * This test exists to fail loudly if that third argument is ever flipped to true: on a 4.3.3+
 * broker the channel would be closed by the server and the round trip below would break, while
 * on the older leg it would keep working - exactly the kind of difference the two-leg matrix is
 * meant to surface before it reaches production.
 */
#[TestDox('Prefetch / QoS on 4.3.3+ (global QoS is denied by default)')]
final class PrefetchQosTest extends IntegrationTestCase
{
    #[TestDox('subscribing uses per-consumer prefetch and completes a round trip without channel errors')]
    public function testSubscribeUsesPermittedPerConsumerPrefetch(): void
    {
        $routingKey = IntegrationTestMessage::getRoutingKey();
        $this->purgeQueuesFor($routingKey);

        $transport = $this->createTransport([IntegrationTestMessage::class]);

        /** @var array<int, string> $received */
        $received = [];
        $this->subscribe(
            $transport,
            $this->subscriptionFor($routingKey),
            function (IntegrationTestMessage $message) use (&$received): void {
                $received[] = $message->getPayload();
            },
        );

        $transport->publish(new IntegrationTestMessage(payload: 'qos-probe'));
        $this->pollFor($transport, 5.0);

        self::assertSame(['qos-probe'], $received, 'round trip failed - the broker may have refused the QoS request');
    }
}
