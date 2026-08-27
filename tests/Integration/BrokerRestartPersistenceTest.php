<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration;

use JTL\Nachricht\Integration\Fixtures\IntegrationTestCase;
use JTL\Nachricht\Integration\Fixtures\RestartTestMessage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * EA-8268 acceptance criterion: "Long delays survive a broker node restart (the fork stores
 * bodies on disk per node)". This matters because the plugin swap is a storage engine swap -
 * the archived plugin kept scheduled messages in Mnesia, the CloudAMQP fork keeps metadata in
 * Khepri and bodies in Leveled. If the fork dropped scheduled messages on an ordinary restart,
 * every deploy would silently lose pending retries.
 *
 * Scope, deliberately narrow: this covers a SINGLE NODE restart. It says nothing about a
 * rolling restart of the STAGE/PROD cluster (amq01-03-stg-ddu01), where Khepri's clustering
 * behaviour is what decides the outcome - that stays a manual STAGE check. It also does not
 * cover the plugin swap itself, during which in-flight scheduled messages are known to be lost
 * (there is no migration path between Mnesia and Leveled).
 *
 * Runs only where a restart command is configured (see AMQP_TEST_BROKER_RESTART_CMD); it is
 * skipped locally and in any environment where the test process must not restart the broker.
 */
#[Group('broker-restart')]
#[TestDox('Broker restart: durability of scheduled messages (single node)')]
final class BrokerRestartPersistenceTest extends IntegrationTestCase
{
    private const DELAY_SECONDS = 25;

    #[TestDox('a message scheduled before a single-node restart is still delivered afterwards')]
    public function testScheduledMessageSurvivesSingleNodeRestart(): void
    {
        $restartCommand = $this->requireBrokerRestartCapability();

        $routingKey = RestartTestMessage::getRoutingKey();
        $this->purgeQueuesFor($routingKey);

        // Bind the queue and schedule the message, then drop this connection - the restart
        // kills it anyway, and a fresh transport has to pick the message up afterwards.
        $transport = $this->createTransport([RestartTestMessage::class]);
        $this->subscribe(
            $transport,
            $this->subscriptionFor($routingKey),
            static function (RestartTestMessage $message): void {
            },
        );
        $transport->publish(
            new RestartTestMessage(payload: 'survives-restart', delay: self::DELAY_SECONDS),
            self::DELAY_SECONDS,
        );
        // Release through the base class so the tracked reference goes too - otherwise the dead
        // connection is closed later, from __destruct(), and throws a broken-pipe error.
        unset($transport);
        $this->releaseTransport();

        $this->restartBroker($restartCommand);

        // Reconnect from scratch, exactly as a restarted consumer process would.
        $reconnected = $this->createTransport([RestartTestMessage::class]);

        /** @var array<int, string> $received */
        $received = [];
        $this->subscribe(
            $reconnected,
            $this->subscriptionFor($routingKey),
            function (RestartTestMessage $message) use (&$received): void {
                $received[] = $message->getPayload();
            },
        );

        // Generous window: the delay may already have elapsed during the restart, in which case
        // the message is due immediately, or it may still have time left to run.
        $this->pollFor($reconnected, self::DELAY_SECONDS + 40.0);

        self::assertSame(
            ['survives-restart'],
            $received,
            'a scheduled message did not survive a single-node broker restart',
        );
    }
}
