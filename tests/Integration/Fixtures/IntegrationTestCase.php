<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 */

namespace JTL\Nachricht\Integration\Fixtures;

use Closure;
use JTL\Generic\StringCollection;
use JTL\Nachricht\Contract\Message\AmqpTransportableMessage;
use JTL\Nachricht\Listener\ListenerProvider;
use JTL\Nachricht\Message\Cache\MessageCache;
use JTL\Nachricht\Serializer\PhpMessageSerializer;
use JTL\Nachricht\Transport\Amqp\AmqpConnectionFactory;
use JTL\Nachricht\Transport\Amqp\AmqpConnectionSettings;
use JTL\Nachricht\Transport\Amqp\AmqpTransport;
use JTL\Nachricht\Transport\SubscriptionSettings;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PHPUnit\Framework\TestCase;

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
 */
abstract class IntegrationTestCase extends TestCase
{
    private ?AmqpTransport $transport = null;

    /**
     * @param array<class-string<AmqpTransportableMessage>> $messageClassList
     */
    protected function createTransport(array $messageClassList): AmqpTransport
    {
        $listenerCache = [];
        foreach ($messageClassList as $messageClass) {
            $listenerCache[$messageClass] = [
                // Content is never resolved (AmqpTransport only calls eventHasListeners()) - a
                // single non-empty entry is enough to make eventHasListeners() return true.
                'listenerList' => [['listenerClass' => 'noop', 'method' => 'noop']],
                'routingKey' => $messageClass::getRoutingKey(),
            ];
        }

        $this->transport = new AmqpTransport(
            $this->connectionSettings(),
            new AmqpConnectionFactory(),
            new PhpMessageSerializer(),
            new ListenerProvider(new NullContainer(), new MessageCache($listenerCache)),
        );

        return $this->transport;
    }

    protected function connectionSettings(): AmqpConnectionSettings
    {
        return new AmqpConnectionSettings(
            host: getenv('AMQP_TEST_HOST') ?: 'localhost',
            port: (int)(getenv('AMQP_TEST_PORT') ?: 5672),
            httpPort: getenv('AMQP_TEST_HTTP_PORT') ?: '15672',
            user: getenv('AMQP_TEST_USER') ?: 'guest',
            password: getenv('AMQP_TEST_PASSWORD') ?: 'guest',
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

    protected function subscriptionFor(string $routingKey): SubscriptionSettings
    {
        return new SubscriptionSettings(
            StringCollection::from(AmqpTransport::MESSAGE_QUEUE_PREFIX . $routingKey),
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

    protected function tearDown(): void
    {
        unset($this->transport);
        parent::tearDown();
    }
}
