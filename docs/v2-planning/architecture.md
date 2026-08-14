# Architecture (V2)

All current-state snippets below are copied from `src/`; all V2 snippets are proposals.
Nothing has been written to `src/`.

## Current call site and contracts (verified)

`src/Middleware/MessageHandlerMiddleware.php`:

```php
final readonly class MessageHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private MessageHandlerResolverInterface $resolver,
    ) {}

    #[Override]
    public function process(
        MessageInterface $message,
        MessageHandlerInterface $handler,
    ): ResultInterface {
        $result = $this->resolver->resolve($message)->handle($message);

        return $handler->handle($result);
    }
}
```

## What changes and what stays

### Pipeline side, renamed, contract unchanged

The pipeline continuation contract keeps `handle()`, renamed to `PipelineHandlerInterface`
(see [MessageHandlerInterface refactor](./message-handler-interface-refactor.md)):

```php
/** @internal */
interface PipelineHandlerInterface
{
    public function handle(MessageInterface $message): ResultInterface;
}
```

`MiddlewareInterface::process()`'s second parameter, `Next`, and `EmptyPipelineHandler`
implement this contract directly; `MiddlewarePipe` gets it through
`MiddlewarePipelineInterface`. `MessageBusInterface` either extends it or declares
`handle()` directly (open decision in the refactor doc). Their `#[Override]` attributes on
`handle()` remain valid.

### Resolved-handler side, no forced method (changed)

`CommandHandlerInterface` and `QueryHandlerInterface` become markers. They no longer extend
`MessageHandlerInterface`, so they force no method:

```php
namespace Webware\MessageBus;

/** @api Marker for command handlers. */
interface CommandHandlerInterface {}

/** @api Marker for query handlers. */
interface QueryHandlerInterface {}

/** @api */
interface MessageHandlerResolverInterface
{
    public function resolve(MessageInterface $message): CommandHandlerInterface|QueryHandlerInterface;
}
```

Consequences:

- A handler implements the marker and declares only the method its wired strategy needs.
- `handle()` is no longer forced, so a `ClassnameStrategy` handler does not carry a dead
  method.
- The `#[Override]` attribute can no longer sit on a handler's `handle()`, because nothing
  declares it to override. Handle-based handlers drop that one attribute; their method body
  and signature are unchanged.

## The strategy seam

```php
namespace Webware\MessageBus;

/** @api */
interface StrategyInterface
{
    public function handlerMethod(MessageInterface $message): string;
}
```

`handlerMethod()` is the sole authority for choosing the handler method name. The middleware only
depends on `StrategyInterface`; it never knows which concrete strategy is wired.

## Shipped strategy 1, `HandleStrategy` (default)

```php
namespace Webware\MessageBus\Strategy;

use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\StrategyInterface;

final readonly class HandleStrategy implements StrategyInterface
{
    public function handlerMethod(MessageInterface $message): string
    {
        return 'handle';
    }
}
```

Returns `'handle'`. Handle-based handlers declare `handle()` themselves and keep working.
Because the marker no longer guarantees `handle()`, the middleware's `is_callable` guard
still applies on this path, but it always passes for any handler that declares `handle()`.

## Shipped strategy 2, `ClassnameStrategy` (opt-in)

```php
namespace Webware\MessageBus\Strategy;

use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\StrategyInterface;

final readonly class ClassnameStrategy implements StrategyInterface
{
    public function handlerMethod(MessageInterface $message): string
    {
        $fqcn = $message::class;

        // App\Command\CreateUser -> createUser
        return lcfirst(substr($fqcn, (int) strrpos($fqcn, '\\') + 1));
    }
}
```

| Message class | Method the handler declares |
| --- | --- |
| `App\Command\CreateUser` | `createUser(CreateUser $message): CommandResult` |
| `App\Query\FindUser` | `findUser(FindUser $message): QueryResult` |

A consumer opts in by changing one container entry (`StrategyInterface::class`).

## Middleware, the target call site

```php
namespace Webware\MessageBus\Middleware;

use Webware\MessageBus\Exception\HandlerMethodNotFoundException;
use Webware\MessageBus\MessageHandlerResolverInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\PipelineHandlerInterface;
use Webware\MessageBus\ResultInterface;
use Webware\MessageBus\StrategyInterface;

final readonly class MessageHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private MessageHandlerResolverInterface $resolver,
        private StrategyInterface $strategy,
    ) {}

    public function process(
        MessageInterface $message,
        PipelineHandlerInterface $next,
    ): ResultInterface {
        $resolved = $this->resolver->resolve($message);
        $method   = $this->strategy->handlerMethod($message);

        if (! is_callable([$resolved, $method])) {
            throw HandlerMethodNotFoundException::forMethod($resolved, $method);
        }

        $result = $resolved->{$method}($message);

        return $next->handle($result);
    }
}
```

The proposal's one-line form, made explicit:

```php
$result = $this->resolver->resolve($message)->{$this->strategy->handlerMethod($message)}($message);
```

The `is_callable` guard is now **required on every path**, the marker contract guarantees
no method, so `'handle'` and `'createUser'` are equally runtime-checked. The guard turns a
missing method into a library exception instead of a raw `Error`.

## Example handlers

Default (`HandleStrategy`), handle-based handler, one attribute lighter:

```php
namespace App\Command;

use Psl\Type;
use Webware\MessageBus\Command\CommandHandlerInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\ResultInterface;

final readonly class CreateUserHandler implements CommandHandlerInterface
{
    public function handle(MessageInterface $message): ResultInterface
    {
        $command = Type\instance_of(CreateUser::class)->assert($message);

        return new CommandResult($command, MessageStatus::Success, 42);
    }
}
```

Opt-in (`ClassnameStrategy`), no `handle()` at all:

```php
final readonly class CreateUserHandler implements CommandHandlerInterface
{
    public function createUser(CreateUser $message): CommandResult
    {
        return new CommandResult($message, MessageStatus::Success, 42);
    }
}
```

The two handlers differ only in the method they declare; the strategy decides which name is
called.

## Wiring

Config map shape is unchanged:

```php
MessageBusInterface::class => [
    ConfigProvider::COMMAND_MAP_KEY => [
        App\Command\CreateUser::class => App\Command\CreateUserHandler::class,
    ],
];
```

New wiring in `ConfigProvider`:

```php
'aliases' => [
    StrategyInterface::class => Strategy\HandleStrategy::class,      // default
    // StrategyInterface::class => Strategy\ClassnameStrategy::class, // opt-in
],
```

`Container\MessageHandlerMiddlewareFactory` fetches `StrategyInterface::class` and injects it
alongside the resolver.

## Resolver (V2)

Same lookup flow, new return type and validation target:

```php
final readonly class MessageHandlerResolver implements MessageHandlerResolverInterface
{
    public function __construct(private ContainerInterface $container) {}

    public function resolve(MessageInterface $message): CommandHandlerInterface|QueryHandlerInterface
    {
        // ... locate handler class in query_map/command_map as today ...

        $handler = $this->container->get($handlerClass);

        if (! $handler instanceof $expectedType) {
            throw InvalidConfigurationException::fromInvalidHandler($handlerClass, $handler, $expectedType);
        }

        return $handler;
    }
}
```

The resolver still validates `instanceof` against the command/query marker and fails fast on
misconfigured maps. Whether the handler has the strategy's method is the middleware's job,
the resolver has no strategy.

## Error surface

| Failure | Source | Behavior |
| --- | --- | --- |
| Unmapped message | resolver | `InvalidConfigurationException` (unchanged) |
| Handler missing from container | resolver | `ServiceNotFoundException` (unchanged) |
| Mapped class is not a command/query handler | resolver | `InvalidConfigurationException` (unchanged) |
| Handler lacks the strategy's method | middleware | `HandlerMethodNotFoundException` (new, any path) |
| Message does not satisfy the target method's parameter type | engine | `TypeError` |
| Handler method returns a non-result | engine | `TypeError` at `process(): ResultInterface` |

`HandlerMethodNotFoundException` is a new exception in `Webware\MessageBus\Exception`,
extending `InvalidArgumentException`, with a factory like
`forMethod(CommandHandlerInterface|QueryHandlerInterface $handler, string $method)`.

