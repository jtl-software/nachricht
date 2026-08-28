<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 21/05/19
 */

namespace JTL\Nachricht\Transport\Amqp;

use DateTimeImmutable;
use Closure;
use JTL\Nachricht\Contract\Message\AmqpTransportableMessage;
use JTL\Nachricht\Contract\Transport\Consumer;
use JTL\Nachricht\Dispatcher\AmqpDispatcher;
use JTL\Nachricht\Log\EchoLogger;
use JTL\Nachricht\Transport\SubscriptionSettings;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Psr\Log\LoggerInterface;

class AmqpConsumer implements Consumer
{
    private const EXIT_SIGNAL_LIST = [SIGINT, SIGTERM, SIGHUP, SIGQUIT];

    private LoggerInterface $logger;

    private bool $shouldConsume;

    /**
     * AmqpConsumer constructor.
     * @param AmqpTransport $transport
     * @param AmqpDispatcher $dispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly AmqpTransport $transport,
        private readonly AmqpDispatcher $dispatcher,
        ?LoggerInterface $logger = null
    ) {
        $this->shouldConsume = true;
        if ($logger === null) {
            $this->logger = new EchoLogger();
        } else {
            $this->logger = $logger;
        }
    }

    /**
     * @param SubscriptionSettings $subscriptionSettings
     * @param int $timeout A timeout in seconds how long a poll will wait until it release polling for incoming messages
     */
    public function consume(SubscriptionSettings $subscriptionSettings, int $timeout = 20): void
    {
        $this->setupSignalHandlers();

        $callback = $this->createCallback();
        $this->transport->subscribe($subscriptionSettings, $callback);

        $ttl = $subscriptionSettings->getTtl();
        if ($ttl >= 0) {
            $endTime = new DateTimeImmutable("+ {$ttl} SECONDS");
        }

        $renewOnIdle = $this->shouldRenewSubscriptionOnIdle();

        do {
            try {
                $this->transport->poll($timeout);
            } catch (AMQPTimeoutException $e) {
                if ($renewOnIdle) {
                    $this->transport->renewSubscription($subscriptionSettings, $callback);
                }
            }

            // Checked outside the try/catch on purpose: an idle queue makes every poll() time
            // out, so evaluating the ttl only after a successful poll meant a consumer with a
            // ttl never terminated once its queue went quiet - it just renewed forever.
            if (isset($endTime) && $endTime <= new DateTimeImmutable()) {
                $this->shouldConsume = false;
            }
        } while ($this->shouldConsume);

        $this->logger->info('Consumer has been shut down');
    }

    /**
     * A poll timeout only means "nothing arrived". Renewing the subscription in response is a
     * workaround from EA-3010 for consumers that silently stopped receiving: the cancel/consume
     * round trip fails loudly on a broker that no longer answers, so the process dies and gets
     * restarted.
     *
     * It has a cost though. A message that becomes deliverable while the subscription is being
     * renewed is lost to the client: the broker counts it as delivered, the callback never runs,
     * and it stays UNACKED until the connection goes away - never handled, never retried, never
     * dead-lettered. On a quiet queue that window opens on every poll timeout.
     *
     * A heartbeat detects an unresponsive broker just as quickly and without that window, so it
     * makes the workaround unnecessary. When one is configured we therefore leave the
     * subscription alone; without one we keep renewing, because dropping it there would bring
     * back the silent hang EA-3010 was about.
     */
    private function shouldRenewSubscriptionOnIdle(): bool
    {
        return $this->transport->getConnectionSettings()->getHeartbeat() === 0;
    }

    /**
     * @return Closure
     */
    private function createCallback(): Closure
    {
        return function (AmqpTransportableMessage $message) {
            $this->dispatcher->dispatch($message);
        };
    }

    /**
     * @return Closure
     */
    private function createSignalCallback(): Closure
    {
        return function () {
            $this->shouldConsume = false;
            $this->logger->info('SIGTERM received. Shutting down consumer gracefully');
        };
    }

    private function setupSignalHandlers(): void
    {
        foreach (self::EXIT_SIGNAL_LIST as $signal) {
            pcntl_signal($signal, $this->createSignalCallback());
        }
    }
}
