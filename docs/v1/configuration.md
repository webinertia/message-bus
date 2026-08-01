# Configuration &amp; Container Integration

## `ConfigProvider`

Registered via `extra.laminas.config-provider` in composer.json for auto-discovery by
`Laminas\ConfigAggregator`/Mezzio. Provides:

- `getDependencies()` — the `aliases`/`factories`/`invokables` needed to wire up `MessageBus`,
  `MessageHandlerResolver`, `MiddlewarePipe`, and `MessageHandlerMiddleware` in a PSR-11 container.
- `getCommandMap()` / `getQueryMap()` — empty by default; consuming applications extend this
  configuration (under the `command_map`/`query_map` keys) to map their own command/query classes to
  handler classes.
- `getMiddleware()` — the default `middleware_pipeline` entry, registering
  `Middleware\MessageHandlerMiddleware` at `ConfigProvider::DEFAULT_PRIORITY` (1) so it runs last.

All three sections are merged under the top-level `Webware\MessageBus\MessageBusInterface::class`
config key.

### Mapping a command/query to a handler

Extend the `command_map`/`query_map` in your application's config (e.g. via a `ConfigAggregator`
provider or a plain `config/autoload/*.global.php` file) and register the handler in the container:

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

### Adding custom middleware

Register the middleware as a service, then add it to `middleware_pipeline` with a `priority`. Higher
priority runs earlier; `MessageHandlerMiddleware` is registered at priority `1` so it runs last by
default — give your middleware a higher priority to run before the handler is resolved:

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

Builds a `MiddlewarePipe` and pipes in every middleware listed under the `middleware_pipeline`
config key, ordered by `priority` (higher runs first; ties broken by declaration order). Middleware
class-strings that aren't registered in the container are silently skipped.

## `Container\MessageHandlerMiddlewareFactory`

Builds a `Middleware\MessageHandlerMiddleware`, injecting the container's
`MessageHandlerResolverInterface` service.

## `functions\collection_mapper_factory()`

Returns a callable that validates each `middleware_pipeline` entry contains the given key (e.g.
`'middleware'`), throwing `Exception\InvalidConfigurationException` otherwise. Used by
`MiddlewarePipeFactory` when mapping raw config entries.

## `functions\priority_queue_reducer_factory()`

Returns an `array_reduce()` callback that inserts middleware config entries into an `SplPriorityQueue`,
keyed by their `priority` (defaulting to 1) with a decrementing serial number as a tiebreaker to
preserve declaration order for equal priorities.
