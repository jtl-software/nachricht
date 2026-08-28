![Testing](https://github.com/jtl-software/nachricht/workflows/Testing/badge.svg)

# Nachricht

Nachricht is a message dispatcher which focuses on distributing workloads.

## Features

* Directly dispatch messages
* Dispatch messages via AMQP
* auto-discovery to find and create AMQP Messages queues
* dead-lettering mechanism    
 
## Requirements
A PSR-11 compatible container (we recommend the [Symfony DependencyInjection component](https://symfony.com/doc/current/components/dependency_injection.html))
is required. The instances of listeners will be obtained from the container
via `$container->get($listenerClass)`.  

The RabbitMQ [delayed message exchange plugin](https://github.com/rabbitmq/rabbitmq-delayed-message-exchange) may be installed
before using Nachricht to make sure you can work with message delay.

## Usage

Create an message class by implementing `JTL\Nachricht\Contract\Message\Message`.
 
```php
use JTL\Nachricht\Contract\Message\Message;

class DummyMessage implements Message
{
    private string $data;

    public function __construct(string $data)
    {
        $this->data = $data;
    }

    public function getData(): string
    {
        return $this->data;
    }
}
```

Create a listener class by implementing `JTL\Nachricht\Contract\Listener\Listener`

```php
use JTL\Nachricht\Contract\Listener\Listener;

class DummyListener implements Listener
{
    public function listen(DummyMessage $event): void
    {
        echo 'Dummy Listener called: ' . $event->getData() . "\n";
    }
}
``` 

Emit the Event

```php
$emitter = $container->get(DirectEmitter::class);

$event = new FooMessage('Test');

$emitter->emit($event); 
```

Output
```php
# php examples/DirectEmit/DirectEmit.php
FooListener called: Test 
```

## Emit delayed messages

A delay can be used to make a message invisible for the consumer until a defined time is reached. There are two types 
of delay available 

On message construct: delay a message when it is getting emitted. 
You can specify such a delay (in seconds) when constructing a new message instance.
```php
$event = new DelayedDummyAmqpMessage(data: 'Test', delay: 3);
$emitter->emit($event); 
```

To specify a retry delay overwrite method `getRetryDelay(): int` method (default retry delay is set to 3 seconds).
Such delay will be used every time a Listener facing an Error when cause jtl/nachricht to re-queue the message

You can find more examples in the `example` directory.

## Connection heartbeat

A consumer talking to a broker that stopped answering has to notice somehow. Historically it
probed for this by tearing down and re-creating its subscription on every poll timeout. That
works, but it opens a window: a message becoming deliverable while the subscription is being
renewed is counted as delivered by the broker and never reaches the listener, so it stays
UNACKED forever - not handled, not retried, not dead-lettered.

A heartbeat detects an unresponsive broker just as quickly and without that window, so it is
**on by default** (60s, matching RabbitMQ's own default). With a heartbeat in effect the consumer
leaves its subscription alone while idling.

```php
$settings = new AmqpConnectionSettings(
    host: 'localhost',
    port: 5672,
    httpPort: '15672',
    user: 'guest',
    password: 'guest',
    // heartbeat: null  => 60s (default)
    // heartbeat: 30    => 30s
    // heartbeat: 0     => off, and the consumer renews its subscription on idle again
);
```

Via `AmqpTransportFactory` the same is available as a `heartbeat` key in the settings array; an
absent or empty value means "use the default", an explicit `0` switches it off.

One consequence worth knowing: php-amqplib requires the socket read/write timeout to be at least
twice the heartbeat, and it uses that single value for reads *and* writes. With the default
heartbeat it is therefore derived as 120s, where it used to be `timeout` (3s). The connection and
channel RPC timeouts stay short, so connecting and RPCs still fail fast, but a publish to a dead
broker can block longer than before. Lower the heartbeat, or set `readWriteTimeout` explicitly,
if that matters for your callers.

