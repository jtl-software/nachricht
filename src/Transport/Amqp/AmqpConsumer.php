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
use JTL\Nachricht\Transport\SubscriptionSettings;

class AmqpConsumer implements Consumer
{
    /**
     * @var AmqpTransport
     */
    private $transport;

    /**
     * @var AmqpDispatcher
     */
    private $dispatcher;


    /**
     * AmqpConsumer constructor.
     * @param AmqpTransport $client
     * @param AmqpDispatcher $dispatcher
     */
    public function __construct(AmqpTransport $client, AmqpDispatcher $dispatcher)
    {
        $this->transport = $client;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param SubscriptionSettings $subscriptionSettings
     */
    public function consume(SubscriptionSettings $subscriptionSettings): void
    {
        $this->transport->subscribe($subscriptionSettings, $this->createCallback());

        while (true) {
            $this->transport->poll();
        }
    }

    /**
     * @return Closure
     */
    private function createCallback(): Closure
    {
        return function (AmqpTransportableMessage $event) {
            $this->dispatcher->dispatch($event);
        };
    }
}
