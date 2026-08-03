# Getting Started

## Requirements

- PHP `~8.4.1 || ~8.5.0`
- A PSR-11 container. This library is developed and tested against
  [`laminas/laminas-servicemanager`](https://github.com/laminas/laminas-servicemanager) (`^4.0.0`), but
  any PSR-11 implementation works.

## Installation

```bash
composer require webware/message-bus
```

The package registers `Webware\MessageBus\ConfigProvider` via composer's
`extra.laminas.config-provider`, so it's auto-discovered by
`Laminas\ConfigAggregator\ConfigAggregator` (and therefore by Mezzio) — no manual config file needed.

If you're not using `ConfigAggregator`, merge the provider's output into your own configuration
manually:

```php
$config = array_merge_recursive(
    (new Webware\MessageBus\ConfigProvider())(),
    /* ...your application config... */
);
```

## What gets registered

`ConfigProvider` wires up the following services (see
[Configuration &amp; Container Integration](./api-reference/configuration.md) for details):

- `MessageBusInterface` -> `MessageBus`
- `MiddlewarePipelineInterface` -> `MiddlewarePipe`
- `MessageHandlerResolverInterface` -> `MessageHandlerResolver`
- `Middleware\MessageHandlerMiddleware` (registered in the pipeline by default, at the lowest priority
  so it runs last)

None of this does anything useful yet, though — you still need to:

1. Define a command or query (implementing `Command\CommandInterface` or `Query\QueryInterface`).
2. Define its handler (implementing `CommandHandlerInterface` or `QueryHandlerInterface`).
3. Map the message class to the handler class, and register the handler in your container.

See [Usage Examples](./usage-examples.md) for a complete walk-through.

## Dispatching your first message

Once a command/handler pair is mapped and registered (see
[Usage Examples](./usage-examples.md)), dispatching is just:

```php
/** @var Psr\Container\ContainerInterface $container */
$bus = $container->get(Webware\MessageBus\MessageBusInterface::class);

$result = $bus->handle(new App\Command\CreateUser('jane@example.com'));

$result->getStatus(); // MessageStatus::Success or ::Failure
$result->getResult(); // whatever the handler returned
```
