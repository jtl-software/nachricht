# nachricht

`jtl/nachricht` is a PHP library that dispatches messages to listeners, either directly in the
same process or through RabbitMQ (AMQP) with delays, retries and dead-letter queues. Listeners are
found by scanning source files with `nikic/php-parser`, and the result is cached in a PHP file.
It supports PHP 8.3, 8.4 and 8.5 (`composer.json`) and uses `symfony/config`.
`php-amqplib/php-amqplib` is only in `suggest` and `require-dev`: a consumer that uses the AMQP
transport installs it.

> The maintainers develop this repository inside a private workspace that holds their general
> rules for code style, tests and commits. This file contains only what is specific to this
> repository. If a rule you need is not written here, follow the existing code and ask in the pull
> request.

## Where things happen

All code is in the namespace `JTL\Nachricht\` (`src/`, PSR-4). Tests use the same root, mapped to
`tests/`.

| Namespace | Path | What happens there |
|---|---|---|
| `Contract\` | `src/Contract/` | The interfaces consumers implement: `Message`, `AmqpTransportableMessage`, `Listener`, the hooks, `AmqpQueueLister`, `MessageCounter`. |
| `Emitter\` | `src/Emitter/` | `AmqpEmitter` publishes through the transport. `DirectEmitter` calls the listeners in the same process. |
| `Dispatcher\` | `src/Dispatcher/` | `AmqpDispatcher` calls every listener of a consumed message. `CountingAmqpDispatcher` also calls a `MessageCounter`. |
| `Listener\` | `src/Listener/` | `ListenerProvider` gets listener instances from the PSR-11 container and wraps them with the hooks. |
| `Message\` | `src/Message/` | `AbstractAmqpTransportableMessage`: message id, routing key, receive count, retry count, delays. |
| `Message\Cache\` | `src/Message/Cache/` | Listener discovery: file scan, AST visitors, the cache file and its freshness check. |
| `Serializer\` | `src/Serializer/` | `PhpMessageSerializer`: PHP `serialize()` is the wire format. |
| `Transport\` | `src/Transport/` | `SubscriptionSettings`: queue names and consumer lifetime (ttl). |
| `Transport\Amqp\` | `src/Transport/Amqp/` | Connection settings, connection factory, `AmqpTransport` (publish, subscribe, retry, dead-letter) and `AmqpConsumer` (poll loop). |
| `Log\` | `src/Log/` | `EchoLogger`, the default PSR-3 logger: stdout, and `error_log()` from warning up. |

Outside `src/`:

- `tests/` holds the unit tests, in the namespace of the class they test.
- `tests/Integration/` holds the tests against a real RabbitMQ, in `JTL\Nachricht\Integration`.
  `Fixtures/IntegrationTestCase.php` is their base class. `Benchmark/` holds the benchmark.
- `docker/rabbitmq/` builds the test broker image with the delayed message exchange plugin.
- `bin/testbed` wraps that image and the integration suite for local runs; `README.md` describes it.
- `examples/` has its own `composer.json`. CI does not run it.

Entry points: this is a library without HTTP routes or console commands. Consumers call
`AmqpEmitter::emit()`, `DirectEmitter::emit()`, `AmqpConsumer::consume()`,
`MessageCacheCreator::create()` and `AmqpTransportFactory::createTransport()`.

### Flows

Emit: `AmqpEmitter::emit()` -> `AmqpTransport::publish($message, $message->getDelay())` ->
declare the exchange `delayed_exchange` and the queue `msg__<routing key>` -> `basic_publish()`.
Without a delay the message goes through the default exchange straight to the queue. With a delay
it goes to `delayed_exchange` with an `x-delay` header in milliseconds.

Consume: `AmqpConsumer::consume()` -> `AmqpTransport::subscribe()` (declare each queue, prefetch 1
per consumer, `basic_consume`, bind the queue to `delayed_exchange`) -> `AmqpTransport::poll()` in
a loop -> the callback from `AmqpTransport::createCallbackFromHandler()` ->
`PhpMessageSerializer::deserialize()` -> `ListenerProvider::eventHasListeners()` -> the closure from
`AmqpConsumer::createCallback()` -> `AmqpDispatcher::dispatch()` -> the closure from
`ListenerProvider::getListenersForMessage()` -> the listener method -> `AmqpTransport::ack()`.

Failure, in the same callback:

- A listener throws: `handleFailedMessage()` stores the error with `setLastError()`. If
  `isDeadLetter()` is true, the message goes to `dl__<routing key>`. Otherwise it is published
  again with `getRetryDelay()` as the delay. The original delivery is acked in both cases.
- No listener in the cache: the message goes to `missing_listener__<routing key>`.
- The body does not deserialize to an `AmqpTransportableMessage`: the raw message goes to the
  queue `failure`.

Discovery: `MessageCacheCreator::create($cacheFile, $lookupPathList, $isDevelopment,
$excludePathList)` -> recursive `glob()` for `*.php` -> `ResourceCheckerConfigCache::isFresh()`
with `MessageCacheResourceChecker` -> only when stale: php-parser with `NameResolver`,
`ListenerDetector` and `AmqpMessageRoutingKeyExtractor`, then a PHP array written to `$cacheFile`
-> `MessageCacheFileLoader` -> `MessageCache`.

Generated files: the cache file and its `.meta` file are written at the path the caller gives;
the unit tests write `tests/Message/Cache/Fixtures/test.cache` (ignored by git). PHPUnit writes
testdox reports to `build/`. There is no generated source code.

## Commands

All commands run in the repository root, with a local PHP and Composer.

Runtime: a PHP version that `composer.json` allows. The required extensions are the `ext-*`
entries in `composer.json` and in the packages of `composer.lock`. Among them: `ext-pcntl` (this
library), `ext-sockets` and `ext-mbstring` (php-amqplib), `ext-tokenizer` (php-parser), `ext-dom`
and `ext-xmlwriter` (PHPUnit). This list is not complete.

```bash
composer install

composer phpunit                                   # unit suite, no broker needed
vendor/bin/phpunit tests/Emitter/AmqpEmitterTest.php
vendor/bin/phpunit --filter testCanEmitMessageList tests/Emitter/AmqpEmitterTest.php

composer phpstan                                   # level 8, src/ only
composer phpcs-dry                                 # code style check
composer phpcs                                     # code style fix
```

Integration tests need a RabbitMQ with the delayed message exchange plugin and the management
API. These are the steps CI runs, with docker:

```bash
docker build -t nachricht-testbed-rabbitmq ./docker/rabbitmq
docker run -d --name rabbitmq -p 5672:5672 -p 15672:15672 nachricht-testbed-rabbitmq
docker exec rabbitmq rabbitmq-diagnostics check_running    # repeat until it exits 0

composer phpunit-integration                       # without the benchmark and restart groups
vendor/bin/phpunit -c phpunit.integration.xml tests/Integration/RetryMetadataTest.php
composer phpunit-benchmark                         # report in build/benchmark-*.json
AMQP_TEST_BROKER_RESTART_CMD='docker restart rabbitmq' composer phpunit-broker-restart
```

The build arguments `RABBITMQ_VERSION`, `PLUGIN_SOURCE` and `PLUGIN_VERSION` select the broker;
their defaults are in `docker/rabbitmq/Dockerfile`.

Another broker: set `AMQP_TEST_HOST`, `AMQP_TEST_PORT`, `AMQP_TEST_HTTP_PORT`, `AMQP_TEST_USER`,
`AMQP_TEST_PASSWORD` and `AMQP_TEST_VHOST` (defaults: `localhost`, 5672, 15672, `guest`, `guest`,
`/`). Use a separate vhost on a broker that other work uses: the tests purge their queues through
the management API. `AMQP_TEST_HEARTBEAT=0` runs the suite without a heartbeat.

When a precondition is missing:

- Without a reachable broker, every integration test errors on the connection. Nothing is skipped.
- Without `AMQP_TEST_BROKER_RESTART_CMD`, the broker-restart test is skipped and the run exits 0.

CI (`.github/workflows/`, on every push):

- `ci.yaml`: `composer phpcs-dry` on the lowest PHP version, and `vendor/bin/phpunit` plus
  `composer phpstan` on every PHP version of its matrix.
- `rabbitmq-testbed.yml`: builds `docker/rabbitmq` per broker leg and runs
  `composer phpunit-integration` per PHP version and leg, `composer phpunit-benchmark`
  (non-blocking) and `composer phpunit-broker-restart` (lowest PHP version). The leg that floats to
  the newest release is non-blocking. It can also be started by hand with a RabbitMQ version.

## Conventions

- New tests use PHPUnit test doubles, not Mockery, and are marked `#[Test]` with a snake_case
  name that starts with `it_`. All existing tests use `testCamelCase` names and some unit tests
  use Mockery; convert them only when the change needs it.
- New files declare `strict_types`. Some existing files do not.
- An integration test extends `IntegrationTestCase`, uses its own message fixture class (and so
  its own queues), calls `purgeQueuesFor()` first, and describes itself with `#[TestDox]` on the
  class and the method.
- Code that logs takes an optional `LoggerInterface` and falls back to `EchoLogger`, which prints.
  New tests pass a `NullLogger` or a logger stub: both PHPUnit configurations mark a test that
  prints as risky, and `phpunit.xml` fails the run on a warning or a notice.
- No class in `src/` is `final`.

## Boundaries

- The package is published on Packagist as `jtl/nachricht` and is installed by other projects.
  No class is marked `@internal`, so every class in `src/` is public API. Consumers extend
  `AbstractAmqpTransportableMessage`, implement the interfaces in `src/Contract/`, mock concrete
  classes such as `AmqpEmitter` (a `final` class cannot be mocked) and use the queue prefix
  constants of `AmqpTransport`. Ask before you change a public signature, a constant or a default,
  or make a class `final`.
- Queue names, the exchange `delayed_exchange` and the serialized form of a message are a contract
  with messages already stored in a broker. A consumer moves dead letters back by removing the
  `dl__` prefix and calling `AmqpTransport::directPublish()`. Ask before you change a prefix, the
  exchange, the serializer, or a property of `AbstractAmqpTransportableMessage`.
- Releases are git tags in the form `1.2.3` (no `v`). Consumers usually require a caret range
  (`^1.0`), so a new minor or patch tag reaches them with their next `composer update`. A change to
  a public signature, a constant, a queue name or the serialized form therefore needs a new major
  version. GitHub writes the release notes from the pull request labels (`.github/release.yml`):
  `enhancement`, `bug` and `dependencies` get their own category, every other pull request lands
  under "Other Changes". Dependabot sets `dependencies`. There is no CHANGELOG file. Do not tag or
  release without the maintainers.
- Ask before you move `php-amqplib/php-amqplib` to `require`, or change the supported PHP
  versions or the `symfony/config` constraint. The PHP versions are listed in `composer.json`, in
  the matrix of `ci.yaml` and in the `setup` job of `rabbitmq-testbed.yml`; change all three
  together. The `check-code-style` job of `ci.yaml` and the `broker-restart` job of
  `rabbitmq-testbed.yml` run on the lowest version.
- The pinned broker versions are set in `bin/testbed` (code and header comment), in the `setup` job
  of `rabbitmq-testbed.yml`, as defaults in `docker/rabbitmq/Dockerfile` and in `README.md`. Change
  them together.

## Pitfalls

- The default routing key is the class name with `\` replaced by `_`. A renamed or moved message
  class gets a new queue; messages in the old queue stay there.
- `getRoutingKey()` returns the key without a prefix. `MessageCache::getRoutingKeyForMessage()`
  returns it with `msg__`. `dl__` and `missing_listener__` are put in front of the bare key.
- The receive count goes up in `AbstractAmqpTransportableMessage::__wakeup()`, on every
  `unserialize()`, also when a tool or a test only reads a message. A message is dead-lettered when
  the count reaches `getRetryCount()` (`DEFAULT_RETRY_COUNT`, 3 deliveries).
- A dead letter carries its receive count in the body. Moved back unchanged, it goes to `dl__`
  again on its first failure. `AbstractAmqpTransportableMessage::setReceiveCount(0)` resets it; the
  method is not part of `AmqpTransportableMessage`.
- A retry is a new publish, not a nack. The original delivery is acked after every outcome.
- A queue is bound to `delayed_exchange` only in `AmqpTransport::subscribe()`. Integration tests
  subscribe before they publish a delayed message.
- `subscribe()` calls `basic_qos(0, 1, false)`. The third argument (global QoS) stays `false`:
  RabbitMQ 4.3.3 and later deny global QoS by default, and `PrefetchQosTest` checks it.
- The heartbeat defaults to 60 s. The read/write timeout is then at least twice the heartbeat, and
  `AmqpConnectionSettings` throws when an explicit value is lower. With heartbeat `0` the consumer
  renews its subscription on every idle poll timeout.
- In the `AmqpTransportFactory` array, an empty `heartbeat`, `readWriteTimeout` or
  `channelRpcTimeout` means "default", and `heartbeat` `"0"` means "off". For `timeout` and `vhost`
  an empty string is used as it is.
- With `$isDevelopment = false` an existing cache file is never rebuilt; it must be deleted to pick
  up a new listener. With `true` it is rebuilt when a file in the lookup paths is added, removed or
  changed.
- The scan loads every class in the lookup paths through the autoloader (`class_implements()`).
- Every public method of a `Listener` whose first parameter is a `Message` class is a handler. The
  method name does not matter. In a `Listener`, give the first parameter of every public method,
  the constructor included, a single non-nullable class type, or no parameter at all.
- When an `AfterMessageErrorHook::onError()` does not rethrow, the message counts as handled: no
  retry. `AfterMessageHook::after()` runs in a `finally` block.
- When one of several listeners of a message throws, the whole message is retried, and the
  listeners that already ran run again.
- `AmqpConsumer::consume()` installs handlers for SIGINT, SIGTERM, SIGHUP and SIGQUIT; the loop ends
  after the current poll. The handlers keep the consumer alive, so `IntegrationTestCase::tearDown()`
  resets them.
- `directPublish()` takes the routing key from the delivery info of the `AMQPMessage`, which is set
  only on a message received from the broker.
- The queue counters of the management API lag behind. Use `pollUntil()` or
  `assertQueueCountersEventually()` instead of one read.

## Where to find more

- `README.md`: usage, delays, the heartbeat and how the tests run, including `bin/testbed`.
- `examples/`: emitter, consumer and container setup.
- [php-amqplib](https://github.com/php-amqplib/php-amqplib).
- The delayed message exchange plugin that `docker/rabbitmq/install-plugin.sh` installs: the
  [CloudAMQP fork](https://github.com/cloudamqp/rabbitmq-delayed-message-exchange) (default) or the
  [archived original](https://github.com/rabbitmq/rabbitmq-delayed-message-exchange).
