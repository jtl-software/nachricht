<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: mbrandt
 * Date: 21/05/19
 */

namespace JTL\Nachricht\Transport\Amqp;

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
        private AmqpTransport $transport,
        private AmqpDispatcher $dispatcher,
        LoggerInterface $logger = null
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
            $endTime = new \DateTimeImmutable("+ {$ttl} SECONDS");
        }

        do {
            try {
                $this->transport->poll($timeout);
                if (isset($endTime) && $endTime <= new \DateTimeImmutable()) {
                    $this->shouldConsume = false;
                }
            } catch (AMQPTimeoutException $e) {
                $this->transport->renewSubscription($subscriptionSettings, $callback);
            }
        } while ($this->shouldConsume);

        $this->logger->info('Consumer has been shut down');
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
