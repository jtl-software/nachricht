[![Build Status](https://travis-ci.org/jtl-software/nachricht.svg?branch=master)](https://travis-ci.org/jtl-software/nachricht)

# Nachricht

Nachricht is an event dispatcher which focuses on distributing workloads.

## Features

* Directly dispatch events
* Dispatch events via AMQP   
 
## Requirements
A PSR-11 compatible container (we recommend the [Symfony DependencyInjection component](https://symfony.com/doc/current/components/dependency_injection.html))
is required. The instances of listeners will be obtained from the container
via `$container->get($listenerClass)`.

## Usage

Create an event class by extending `JTL\Nachricht\Event\AbstractEvent`.
Listeners have to be registered in the `getListenerClassList()` method.
 
```php
class DummyEvent extends AbstractEvent
{
    private $data;

    public function __construct(string $data)
    {
        $this->data = $data;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function getListenerClassList(): StringCollection
    {
        return StringCollection::from(DummyListener::class);
    }
}
```

Create a listener class by implementing `JTL\Nachricht\Contract\Listener\Listener`

```php
class DummyListener implements Listener
{
    public function __invoke(Event $event): void
    {
        echo 'Dummy Listener called: ' . $event->getData() . "\n";
    }
}
``` 

Emit the Event

```php
$emitter = $container->get(DirectEmitter::class);

$event = new FooEvent('Test');

$emitter->emit($event); 
```

Output
```php
# php examples/DirectEmit/DirectEmit.php
FooListener called: Test 
```

You can find more examples in the `example` directory.

