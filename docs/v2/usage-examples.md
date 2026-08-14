# Usage Examples

## Defining a command and its handler

A command is any class implementing `Command\CommandInterface`:

```php
namespace App\Command;

use Webware\MessageBus\Command\CommandInterface;

final readonly class CreateUser implements CommandInterface
{
    public function __construct(
        public string $email,
    ) {}
}
```

`CommandHandlerInterface` is a marker. It declares no method, so the handler declares the method its
wired strategy expects. Under the default `HandleStrategy` that method is `handle()`:

```php
namespace App\Command;

use Psl\Type;
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\ResultInterface;

final readonly class CreateUserHandler implements CommandHandlerInterface
{
    public function handle(MessageInterface $message): ResultInterface
    {
        // narrow from the broad message type to this specific command
        $command = Type\instance_of(CreateUser::class)->assert($message);

        // ...create the user...
        $userId = 42;

        return new CommandResult($command, MessageStatus::Success, $userId);
    }
}
```

There is no `#[Override]` on `handle()`: the marker declares nothing to override. The method parameter
is yours to choose. Declaring it as `MessageInterface|CommandInterface` is runtime-safe (the union is
at least as wide as `MessageInterface`) and tells static analysis that this handler only receives
commands, without narrowing too early. You still need `Type\instance_of()` (or an `instanceof` check)
to narrow to `CreateUser` itself.

### Named-method handlers

Opt into `ClassnameStrategy` and the handler declares a method named after the message's short class
name (`CreateUser` -> `createUser`), typed directly to the concrete class:

```php
namespace App\Command;

use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;

final readonly class CreateUserHandler implements CommandHandlerInterface
{
    public function createUser(CreateUser $message): CommandResult
    {
        // ...create the user...
        $userId = 42;

        return new CommandResult($message, MessageStatus::Success, $userId);
    }
}
```

No `handle()`, no narrowing assertion. See [Strategy](./api-reference/strategy.md) for how to wire the
strategy.

## Mapping the command to its handler

Extend the `command_map` in your application config and register the handler with your container:

```php
use Webware\MessageBus\ConfigProvider;
use Webware\MessageBus\MessageBusInterface;

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

Queries follow the exact same shape under `ConfigProvider::QUERY_MAP_KEY`, using
`Query\QueryInterface`/`QueryHandlerInterface` and `Query\QueryResult` instead.

To use `ClassnameStrategy`, add one alias:

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

## Dispatching

```php
/** @var Psr\Container\ContainerInterface $container */
$bus = $container->get(MessageBusInterface::class);

$result = $bus->handle(new App\Command\CreateUser('jane@example.com'));

$result->getStatus(); // MessageStatus::Success or ::Failure
$result->getResult(); // 42, in this example
```

## Writing custom middleware

Middleware implements `MiddlewareInterface` and decides whether/when to call the next handler in the
pipeline:

```php
namespace App\Middleware;

use Override;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\PipelineHandlerInterface;
use Webware\MessageBus\ResultInterface;

final readonly class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private App\Logging\LoggerInterface $logger,
    ) {}

    #[Override]
    public function process(
        MessageInterface $message,
        PipelineHandlerInterface $next,
    ): ResultInterface {
        $this->logger->info('Dispatching ' . $message::class);

        return $next->handle($message);
    }
}
```

Register it as a service and add it to `middleware_pipeline` with a `priority`. Higher priority runs
earlier; `Middleware\MessageHandlerMiddleware` is registered at priority `1`
(`ConfigProvider::DEFAULT_PRIORITY`) so it runs last by default. Give your middleware a higher priority
to run before the handler is resolved:

```php
use Webware\MessageBus\ConfigProvider;
use Webware\MessageBus\MessageBusInterface;

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

Middleware registered without a `priority` defaults to `1`; entries sharing the same priority run in
the order they were declared. Middleware class-strings that aren't registered in the container are
silently skipped when the pipeline is built (see [Configuration](./api-reference/configuration.md)).

See [Execution Flow](./flow-charts.md#custom-middleware-example) for a diagram of how this
`LoggingMiddleware` example fits into the pipeline, and why it never sees the `CommandResult`.
