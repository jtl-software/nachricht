<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration\Fixtures;

use Closure;
use JTL\Generic\StringCollection;
use JTL\Nachricht\Contract\Message\AmqpTransportableMessage;
use JTL\Nachricht\Dispatcher\AmqpDispatcher;
use JTL\Nachricht\Emitter\AmqpEmitter;
use JTL\Nachricht\Listener\ListenerProvider;
use JTL\Nachricht\Message\Cache\MessageCache;
use JTL\Nachricht\Serializer\PhpMessageSerializer;
use JTL\Nachricht\Transport\Amqp\AmqpConnectionFactory;
use JTL\Nachricht\Transport\Amqp\AmqpConnectionSettings;
use JTL\Nachricht\Transport\Amqp\AmqpConsumer;
use JTL\Nachricht\Transport\Amqp\AmqpTransport;
use JTL\Nachricht\Transport\SubscriptionSettings;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Throwable;

/**
 * Base class for the RabbitMQ testbed integration tests (EA-8268). Every test in this suite
 * talks to a real broker - there is no mocking here, that is the point: these tests exist to
 * catch behavioural differences between the archived rabbitmq-delayed-message-exchange plugin
 * and the maintained CloudAMQP fork on RabbitMQ 4.3.x.
 *
 * Connection target is controlled via env vars so the same tests run against whatever broker
 * the CI job's docker-run step booted:
 *   AMQP_TEST_HOST      default 'localhost'
 *   AMQP_TEST_PORT      default 5672
 *   AMQP_TEST_HTTP_PORT default 15672 (management API, used only to purge queues between tests)
 *   AMQP_TEST_USER      default 'guest'
 *   AMQP_TEST_PASSWORD  default 'guest'
 *   AMQP_TEST_VHOST     default '/' - point the suite at a throwaway vhost when the broker is
 *                       shared with something else (a local dev environment, for instance)
 *   AMQP_TEST_HEARTBEAT default 30 - 0 falls back to the legacy renew-on-idle behaviour
 *
 * Optional:
 *   AMQP_TEST_BROKER_RESTART_CMD  shell command that restarts the broker; tests needing a
 *                                 restart skip themselves when it is not set (see restartBroker)
 */
abstract class IntegrationTestCase extends TestCase
{
    private ?AmqpTransport $transport = null;

    /**
     * Builds a ListenerProvider whose MessageCache knows the given message classes.
     *
     * $listenerMap is optional: pass a listener instance per message class to drive the real
     * production dispatch path (AmqpDispatcher -> ListenerProvider -> container->get()). Message
     * classes without an entry get a placeholder, which is enough for the transport-level tests
     * where AmqpTransport only calls eventHasListeners() and the handler is a plain closure.
     *
     * A message class deliberately left out of $messageClassList has no listeners at all, which
     * is how the missing-listener path gets exercised.
     *
     * @param array<class-string<AmqpTransportableMessage>> $messageClassList
     * @param array<class-string<AmqpTransportableMessage>, object> $listenerMap
     */
    protected function createListenerProvider(array $messageClassList, array $listenerMap = []): ListenerProvider
    {
        $listenerCache = [];
        $services = [];

        foreach ($messageClassList as $messageClass) {
            // ListenerProvider only uses 'listenerClass' as a container key, never as a real
            // class name - a synthetic id keeps two message classes that share a listener
            // implementation from colliding in the container.
            $serviceId = 'listener::' . $messageClass;
            $listenerCache[$messageClass] = [
                'listenerList' => [['listenerClass' => $serviceId, 'method' => 'handle']],
                'routingKey' => $messageClass::getRoutingKey(),
            ];

            if (isset($listenerMap[$messageClass])) {
                $services[$serviceId] = $listenerMap[$messageClass];
            }
        }

        return new ListenerProvider(new ArrayContainer($services), new MessageCache($listenerCache));
    }

    /**
     * @param array<class-string<AmqpTransportableMessage>> $messageClassList
     * @param array<class-string<AmqpTransportableMessage>, object> $listenerMap
     */
    protected function createTransport(array $messageClassList, array $listenerMap = []): AmqpTransport
    {
        return $this->createTransportWithProvider(
            $this->createListenerProvider($messageClassList, $listenerMap),
        );
    }

    protected function createTransportWithProvider(ListenerProvider $listenerProvider): AmqpTransport
    {
        $this->transport = new AmqpTransport(
            $this->connectionSettings(),
            new AmqpConnectionFactory(),
            new PhpMessageSerializer(),
            $listenerProvider,
            // AmqpTransport defaults to EchoLogger, which prints every debug/info line to
            // stdout - that trips PHPUnit's beStrictAboutOutputDuringTests. Tests assert on
            // their own recorded state, not on log output, so a NullLogger is correct here.
            new NullLogger(),
        );

        return $this->transport;
    }

    /**
     * The publish entry point production actually uses (scx-api injects AmqpEmitter, not
     * AmqpTransport). Notably emit() takes the delay from $message->getDelay() instead of an
     * explicit argument, so this is the only way to cover that wiring.
     */
    protected function createEmitter(AmqpTransport $transport): AmqpEmitter
    {
        return new AmqpEmitter($transport);
    }

    /**
     * The consume entry point production actually uses. Wraps the real poll loop, the
     * renewSubscription-on-timeout behaviour, the ttl shutdown and dispatch through
     * AmqpDispatcher - none of which the transport-level pollFor() helper touches.
     */
    protected function createConsumer(AmqpTransport $transport, ListenerProvider $listenerProvider): AmqpConsumer
    {
        return new AmqpConsumer($transport, new AmqpDispatcher($listenerProvider), new NullLogger());
    }

    protected function connectionSettings(): AmqpConnectionSettings
    {
        return new AmqpConnectionSettings(
            host: getenv('AMQP_TEST_HOST') ?: 'localhost',
            port: (int)(getenv('AMQP_TEST_PORT') ?: 5672),
            httpPort: getenv('AMQP_TEST_HTTP_PORT') ?: '15672',
            user: getenv('AMQP_TEST_USER') ?: 'guest',
            password: getenv('AMQP_TEST_PASSWORD') ?: 'guest',
            vhost: getenv('AMQP_TEST_VHOST') ?: '/',
            // Heartbeat on by default: it is the configuration this library recommends, and it
            // is what stops AmqpConsumer from tearing down its subscription on every idle
            // timeout. Set AMQP_TEST_HEARTBEAT=0 to exercise the legacy renew-on-idle path.
            heartbeat: (int)(getenv('AMQP_TEST_HEARTBEAT') !== false ? getenv('AMQP_TEST_HEARTBEAT') : 30),
        );
    }

    protected function managementClient(): RabbitMqManagementClient
    {
        $settings = $this->connectionSettings();

        return new RabbitMqManagementClient(
            $settings->getHost(),
            $settings->getHttpPort(),
            $settings->getUser(),
            $settings->getPassword(),
            $settings->getVhost(),
        );
    }

    /**
     * Purges every queue this message's routing key could end up in (main, dead-letter,
     * missing-listener) so tests sharing a routing key start from a clean slate.
     */
    protected function purgeQueuesFor(string $routingKey): void
    {
        $client = $this->managementClient();
        $client->purgeQueue(AmqpTransport::MESSAGE_QUEUE_PREFIX . $routingKey);
        $client->purgeQueue(AmqpTransport::DEAD_LETTER_QUEUE_PREFIX . $routingKey);
        $client->purgeQueue(AmqpTransport::MISSING_LISTENER_QUEUE_PREFIX . $routingKey);
    }

    protected function purgeFailureQueue(): void
    {
        $this->managementClient()->purgeQueue(AmqpTransport::FAILURE_QUEUE);
    }

    protected function subscriptionFor(string $routingKey, int $ttl = -1): SubscriptionSettings
    {
        return new SubscriptionSettings(
            StringCollection::from(AmqpTransport::MESSAGE_QUEUE_PREFIX . $routingKey),
            $ttl,
        );
    }

    /**
     * Subscribes $handler to $subscription. Deliberately separate from pollFor() - the queue
     * binding must exist BEFORE a message is published to the delayed exchange, otherwise the
     * exchange has nowhere to route the message once its delay elapses and it is silently
     * dropped. Call this before publish(), then pollFor() afterwards.
     *
     * $handler is the same closure AmqpTransport::subscribe() dispatches through - throwing
     * from it exercises the real handleFailedMessage()/retry/dead-letter path, not a simulation.
     */
    protected function subscribe(AmqpTransport $transport, SubscriptionSettings $subscription, Closure $handler): void
    {
        $transport->subscribe($subscription, $handler);
    }

    /**
     * Keeps polling the broker until $timeoutSeconds have elapsed, so the subscribed handler(s)
     * get a chance to fire.
     */
    protected function pollFor(AmqpTransport $transport, float $timeoutSeconds): void
    {
        $deadline = microtime(true) + $timeoutSeconds;

        while (microtime(true) < $deadline) {
            try {
                $transport->poll(1);
            } catch (AMQPTimeoutException) {
                // No message within this 1s poll window - keep going until the overall deadline.
            }
        }
    }

    /**
     * Skips the calling test unless a restart command is configured, so the suite stays runnable
     * against a broker the test process is not allowed to control (a shared local dev broker,
     * for instance). Call this first, before the test publishes anything.
     */
    protected function requireBrokerRestartCapability(): string
    {
        $command = getenv('AMQP_TEST_BROKER_RESTART_CMD');
        if ($command === false || $command === '') {
            self::markTestSkipped('AMQP_TEST_BROKER_RESTART_CMD is not set - cannot restart the broker');
        }

        return $command;
    }

    /**
     * Restarts the broker and waits until it accepts connections again.
     */
    protected function restartBroker(string $command): void
    {
        exec($command . ' 2>&1', $output, $exitCode);
        if ($exitCode !== 0) {
            self::fail("broker restart command failed (exit {$exitCode}): " . implode("\n", $output));
        }

        $this->waitForBroker();
    }

    /**
     * Waits for the broker by completing a real AMQP handshake, not by probing the TCP port: with
     * a published container port the port answers as soon as the port forwarder is up, well
     * before the node can serve AMQP, and the first real connection then dies with a broken pipe.
     */
    private function waitForBroker(float $timeoutSeconds = 90.0): void
    {
        $settings = $this->connectionSettings();
        $factory = new AmqpConnectionFactory();
        $deadline = microtime(true) + $timeoutSeconds;
        $lastError = 'no attempt made';

        while (microtime(true) < $deadline) {
            try {
                $connection = $factory->connect($settings);
                $channel = $connection->channel();
                $channel->close();
                $connection->close();

                return;
            } catch (Throwable $throwable) {
                $lastError = $throwable->getMessage();
                sleep(2);
            }
        }

        self::fail("broker did not serve AMQP again within {$timeoutSeconds}s (last error: {$lastError})");
    }

    /**
     * Drops the tracked transport and forces its connection to close. Swallows connection errors
     * on purpose: AmqpTransport::__destruct() calls close(), which throws when the connection is
     * already gone - true right after a broker restart.
     */
    protected function releaseTransport(): void
    {
        try {
            unset($this->transport);
            gc_collect_cycles();
        } catch (Throwable) {
            // Connection was already dead; nothing left to close.
        }
    }

    /**
     * Polls $condition until it holds or the timeout expires. The management API's queue
     * counters are fed by a stats collector and lag behind the AMQP operations that caused
     * them, so reading them once right after publishing or acking is racy.
     */
    protected function pollUntil(callable $condition, float $timeoutSeconds = 20.0): bool
    {
        $deadline = microtime(true) + $timeoutSeconds;

        do {
            if ($condition() === true) {
                return true;
            }
            usleep(500_000);
        } while (microtime(true) < $deadline);

        return false;
    }

    /**
     * Asserts the queue eventually reports the expected ready/unacknowledged counts.
     */
    protected function assertQueueCountersEventually(
        string $queueName,
        int $expectedReady,
        int $expectedUnacknowledged,
        string $message = '',
    ): void {
        $observed = null;
        $matched = $this->pollUntil(function () use ($queueName, $expectedReady, $expectedUnacknowledged, &$observed): bool {
            $observed = $this->managementClient()->queueCounters($queueName);

            return $observed !== null
                && $observed['ready'] === $expectedReady
                && $observed['unacknowledged'] === $expectedUnacknowledged;
        });

        self::assertTrue($matched, sprintf(
            '%squeue "%s" reported ready=%s unacknowledged=%s, expected ready=%d unacknowledged=%d',
            $message === '' ? '' : $message . ': ',
            $queueName,
            $observed === null ? 'n/a' : (string)$observed['ready'],
            $observed === null ? 'n/a' : (string)$observed['unacknowledged'],
            $expectedReady,
            $expectedUnacknowledged,
        ));
    }

    /**
     * Asserts the broker eventually reports the expected number of consumers on the queue.
     */
    protected function assertConsumerCountEventually(string $queueName, int $expected, string $message = ''): void
    {
        $observed = null;
        $matched = $this->pollUntil(function () use ($queueName, $expected, &$observed): bool {
            $observed = $this->managementClient()->consumerCount($queueName);

            return $observed === $expected;
        });

        self::assertTrue($matched, sprintf(
            '%squeue "%s" reported %s consumers, expected %d',
            $message === '' ? '' : $message . ': ',
            $queueName,
            $observed === null ? 'n/a' : (string)$observed,
            $expected,
        ));
    }

    protected function tearDown(): void
    {
        // AmqpConsumer::consume() installs pcntl signal handlers whose closures are bound to the
        // consumer, and signal handlers live for the whole process. That keeps the consumer -
        // and through it the transport and its open connection - alive after the test that
        // created it has finished, leaving a consumer registered on the broker that then
        // round-robins away the next test's messages. Harmless in production, where the consumer
        // is meant to live as long as the process, but it has to be undone between tests.
        foreach ([SIGINT, SIGTERM, SIGHUP, SIGQUIT] as $signal) {
            pcntl_signal($signal, SIG_DFL);
        }

        $this->releaseTransport();
        parent::tearDown();
    }
}
