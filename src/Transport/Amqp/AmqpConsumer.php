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
use Psr\Log\LoggerInterface;

class AmqpConsumer implements Consumer
{
    private const EXIT_SIGNAL_LIST = [SIGINT, SIGTERM];

    private AmqpTransport $transport;

    private AmqpDispatcher $dispatcher;

    private LoggerInterface $logger;

    private bool $shouldConsume;

    /**
     * AmqpConsumer constructor.
     * @param AmqpTransport $client
     * @param AmqpDispatcher $dispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(AmqpTransport $client, AmqpDispatcher $dispatcher, LoggerInterface $logger = null)
    {
        $this->transport = $client;
        $this->dispatcher = $dispatcher;
        $this->shouldConsume = true;
        if ($logger === null) {
            $this->logger = new EchoLogger();
        } else {
            $this->logger = $logger;
        }
    }

    /**
     * @param SubscriptionSettings $subscriptionSettings
     */
    public function consume(SubscriptionSettings $subscriptionSettings): void
    {
        $this->setupSignalHandlers();
        $this->transport->subscribe($subscriptionSettings, $this->createCallback());

        while ($this->shouldConsume) {
            $this->transport->poll();
        }

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
