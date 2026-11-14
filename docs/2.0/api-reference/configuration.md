# Configuration &amp; Container Integration

## `ConfigProvider`

Registered via `extra.laminas.config-provider` in composer.json for auto-discovery by
`Laminas\ConfigAggregator`/Mezzio. Provides:

- `getDependencies()`: the `aliases`/`factories`/`invokables` needed to wire up `MessageBus`,
  `MessageHandlerResolver`, `MiddlewarePipe`, `MessageHandlerMiddleware`, and the strategies in a
  PSR-11 container.
- `getCommandMap()` / `getQueryMap()`: empty by default; consuming applications extend this
  configuration (under the `command_map`/`query_map` keys) to map their own command/query classes to
  handler classes.
- `getMiddleware()`: the default `middleware_pipeline` entry, registering
  `Middleware\MessageHandlerMiddleware` at `ConfigProvider::DEFAULT_PRIORITY` (1) so it runs last.

All three sections are merged under the top-level `Webware\MessageBus\MessageBusInterface::class`
config key.

### Default wiring

```php
'aliases'    => [
    MessageBusInterface::class             => MessageBus::class,
    MiddlewarePipelineInterface::class     => MiddlewarePipe::class,
    MessageHandlerResolverInterface::class => MessageHandlerResolver::class,
    StrategyInterface::class               => Strategy\HandleStrategy::class,
],
'factories'  => [
    MessageBus::class                          => Container\MessageBusFactory::class,
    MessageHandlerResolver::class              => Container\MessageHandlerResolverFactory::class,
    MiddlewarePipe::class                      => Container\MiddlewarePipeFactory::class,
    Middleware\MessageHandlerMiddleware::class => Container\MessageHandlerMiddlewareFactory::class,
],
'invokables' => [
    Handler\EmptyPipelineHandler::class => Handler\EmptyPipelineHandler::class,
    Strategy\HandleStrategy::class      => Strategy\HandleStrategy::class,
    Strategy\ClassnameStrategy::class   => Strategy\ClassnameStrategy::class,
],
```

### Mapping a command/query to a handler

Extend the `command_map`/`query_map` in your application's config and register the handler in the
container:

```php
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\ConfigProvider;

return [
    'dependencies' => [
        'factories' => [
            App\Command\CreateUserHandler::class => App\Command\CreateUserHandlerFactory::class,
        ],
    ],
    MessageBusInterface::class => [
        ConfigProvider::COMMAND_MAP_KEY => [
            App\Command\CreateUser::class => App\Command\CreateUserHandler::class,
        ],
    ],
];
```

`App\Command\CreateUser` must implement `Command\CommandInterface`, and
`App\Command\CreateUserHandler` must implement `CommandHandlerInterface`. Queries follow the same
pattern under `ConfigProvider::QUERY_MAP_KEY` with `Query\QueryInterface`/`QueryHandlerInterface`.

### Choosing a strategy

```php
use Webware\MessageBus\Strategy\ClassnameStrategy;
use Webware\MessageBus\StrategyInterface;

return [
    'dependencies' => [
        'aliases' => [
            StrategyInterface::class => ClassnameStrategy::class,
        ],
    ],
];
```

### Adding custom middleware

Register the middleware as a service, then add it to `middleware_pipeline` with a `priority`. Higher
priority runs earlier; `MessageHandlerMiddleware` is registered at priority `1` so it runs last by
default. Give your middleware a higher priority to run before the handler is resolved:

```php
use Webware\MessageBus\ConfigProvider;

return [
    'dependencies' => [
        'factories' => [
            App\Middleware\LoggingMiddleware::class => App\Middleware\LoggingMiddlewareFactory::class,
        ],
    ],
    MessageBusInterface::class => [
        ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [
            [
                'middleware' => App\Middleware\LoggingMiddleware::class,
                'priority'   => 10,
            ],
        ],
    ],
];
```

### Dispatching a message

```php
/** @var Psr\Container\ContainerInterface $container */
$bus    = $container->get(MessageBusInterface::class);
$result = $bus->handle(new App\Command\CreateUser('jane@example.com'));

$result->getStatus(); // MessageStatus::Success or ::Failure
$result->getResult(); // the value returned by CreateUserHandler
```

## `Container\MessageBusFactory`

Builds a `MessageBus`, fetching the `MiddlewarePipelineInterface` service from the container. Throws
`Exception\ServiceNotFoundException` if it's not registered.

## `Container\MessageHandlerResolverFactory`

Builds a `MessageHandlerResolver`, injecting the container itself so it can be used later to fetch
mapped handler services.

## `Container\MiddlewarePipeFactory`

Builds a `MiddlewarePipe` and pipes in every middleware listed under the `middleware_pipeline` config
key, ordered by `priority` (higher runs first; ties broken by declaration order). Middleware
class-strings that aren't registered in the container are silently skipped. Throws
`Exception\ServiceNotFoundException` if the `config` service is missing, or
`Exception\InvalidConfigurationException` if the `MessageBusInterface` config key is absent.

## `Container\MessageHandlerMiddlewareFactory`

Builds a `Middleware\MessageHandlerMiddleware`, injecting the container's
`MessageHandlerResolverInterface` and `StrategyInterface` services.

## `functions\collection_mapper_factory()`

Returns a callable that validates each `middleware_pipeline` entry contains the given key (for example
`'middleware'`), throwing `Exception\InvalidConfigurationException` otherwise. Used by
`MiddlewarePipeFactory` when mapping raw config entries.

## `functions\priority_queue_reducer_factory()`

Returns an `array_reduce()` callback that inserts middleware config entries into an `SplPriorityQueue`,
keyed by their `priority` (defaulting to 1) with a descending tie-break counter to preserve declaration
order for equal priorities.
