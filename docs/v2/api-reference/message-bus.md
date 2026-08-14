# Message Bus &amp; Core Contracts

## `MessageInterface`

Marker interface for anything that can travel through the bus. `Command\CommandInterface` and
`Query\QueryInterface` both extend it; `ResultInterface` also extends it so a result can itself be
passed to the terminal handler in the pipeline.

## `MessageBusInterface` / `MessageBus`

`MessageBusInterface` extends `PipelineHandlerInterface` and is the public entry point of the library.
Call `handle(MessageInterface $message): ResultInterface` to dispatch a command or query.

`MessageBus` is the default implementation. It's a thin, `final readonly` wrapper that delegates
directly to a `MiddlewarePipelineInterface` instance.

## `PipelineHandlerInterface`

The pipeline continuation contract: `handle(MessageInterface $message): ResultInterface`. Implemented
by `MessageBusInterface`, `MiddlewarePipelineInterface`, `Next`, and
`Handler\EmptyPipelineHandler`. It names the rest of the pipeline that a middleware hands control to,
and is what `MiddlewareInterface::process()` receives as its second argument.

## `MessageHandlerInterface`

The common supertype for all resolved handlers. `CommandHandlerInterface` and `QueryHandlerInterface`
both extend it, so a single type covers every handler the resolver can return.

## `CommandHandlerInterface` / `QueryHandlerInterface`

Marker interfaces for application-defined command and query handlers. Each extends
`MessageHandlerInterface` and declares no method: the wired `StrategyInterface` chooses which method is
called (see [Strategy](./strategy.md)).

## `MessageHandlerResolverInterface` / `MessageHandlerResolver`

Given a message, looks up its mapped handler class in the `command_map`/`query_map` configuration (see
[Configuration](./configuration.md)) and fetches it from the PSR-11 container.

Returns `MessageHandlerInterface`. Throws `Exception\InvalidConfigurationException` if the message's
class has no map entry or the resolved handler is not an instance of the expected marker, and
`Exception\ServiceNotFoundException` if the `config` service or the mapped handler class isn't
registered in the container.

## `ResultInterface` / `StatusInterface` / `MessageStatus`

`ResultInterface` extends `MessageInterface` and exposes `getResult(): mixed` and
`getStatus(): StatusInterface`, letting a handler's result be sent back through the pipeline as a
message. `Command\CommandResult` and `Query\QueryResult` (see
[Commands &amp; Queries](./commands-and-queries.md)) are the concrete implementations.

`StatusInterface` is an empty marker; `MessageStatus` is the built-in enum implementing it, with
`Success` and `Failure` cases.
