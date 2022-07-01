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

The RabbitMQ [delayed message exchange plugin](https://github.com/rabbitmq/rabbitmq-delayed-message-exchange) has to be installed
before using Nachricht.

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

## Enqueue and retry delay  

You can specify a delay before the message will be enqueued by overriding the `getDelay(): int` method.  
Additionally, you can specify a separate delay in case of an error by overriding the `getRetryDelay(): int` method. 
Both of these delays are also part of the `AbstractAmqpTransportableMessage` constructor. 
Examples can be found in the `DelayedDummyAmqpMessage` and `DummyRetryDelayAmqpMessage` classes.

You can find more examples in the `example` directory.

