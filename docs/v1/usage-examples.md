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

`CommandHandlerInterface::handle()` is typed to the broad `MessageInterface`, but parameter types are
contravariant — an implementation is free to widen a parameter type, just never narrow it. Declaring
the parameter as `MessageInterface|CommandInterface` is still a valid override (the union includes
`MessageInterface`, so it's just as wide as the interface requires), and it tells static analysis tools
(and readers) that this handler only ever receives commands, without any runtime cost:

```php
namespace App\Command;

use Override;
use Psl\Type;
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\ResultInterface;

final readonly class CreateUserHandler implements CommandHandlerInterface
{
    #[Override]
    public function handle(MessageInterface|CommandInterface $message): ResultInterface
    {
        // narrow further, from "some command" to this specific command
        $command = Type\instance_of(CreateUser::class)->assert($message);

        // ...create the user...
        $userId = 42;

        return new CommandResult($command, MessageStatus::Success, $userId);
    }
}
```

The union only buys you the `CommandInterface` half of the contract at the type level (useful if you
need something like `NamedCommandInterface::getName()` before knowing the concrete class); you still
need `Type\instance_of()` (or an `instanceof` check) to narrow to `CreateUser` itself, since nothing
else guarantees that a given `CommandInterface` is the one this handler expects.

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
use Webware\MessageBus\MessageHandlerInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\ResultInterface;

final readonly class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private App\Logging\LoggerInterface $logger,
    ) {}

    #[Override]
    public function process(
        MessageInterface $message,
        MessageHandlerInterface $handler,
    ): ResultInterface {
        $this->logger->info('Dispatching ' . $message::class);

        return $handler->handle($message);
    }
}
```

Register it as a service and add it to `middleware_pipeline` with a `priority`. Higher priority runs
earlier; `Middleware\MessageHandlerMiddleware` is registered at priority `1` (`ConfigProvider::DEFAULT_PRIORITY`)
so it runs last by default — give your middleware a higher priority to run before the handler is
resolved:

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
