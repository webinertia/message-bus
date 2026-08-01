# Message Bus &amp; Core Contracts

## `MessageInterface`

Marker interface for anything that can travel through the bus. `CommandInterface` and `QueryInterface`
both extend it; `ResultInterface` also extends it so a result can itself be passed to the final handler
in the pipeline.

## `MessageBusInterface` / `MessageBus`

`MessageBusInterface` extends `MessageHandlerInterface` and is the public entry point of the library —
call `handle(MessageInterface $message): ResultInterface` to dispatch a command or query.

`MessageBus` is the default implementation. It's a thin wrapper that delegates directly to a
`MiddlewarePipelineInterface&MiddlewarePipe` instance.

## `MessageHandlerInterface`

The common contract implemented by anything capable of handling a message: `MessageBusInterface`,
`CommandHandlerInterface`, `QueryHandlerInterface`, middleware pipelines, and the internal `Next`/
`EmptyPipelineHandler` classes all implement this single `handle()` method.

## `CommandHandlerInterface` / `QueryHandlerInterface`

Marker interfaces for application-defined command and query handlers. Both simply re-declare
`handle(MessageInterface $message): ResultInterface` for clarity/type-narrowing at the edges of an
application; there is no behavioral difference between the two beyond intent.

## `MessageHandlerResolverInterface` / `MessageHandlerResolver`

Given a message, looks up its mapped handler class in the `command_map`/`query_map` configuration
(see [Configuration](./configuration.md)) and fetches it from the PSR-11 container.

Throws `Exception\InvalidConfigurationException` if the message's class has no map entry, and
`Exception\ServiceNotFoundException` if the mapped handler class isn't registered in the container.

## `ResultInterface` / `StatusInterface` / `MessageStatus`

`ResultInterface` extends `MessageInterface` and exposes `getResult(): mixed` and
`getStatus(): StatusInterface`, letting a handler's result be sent back through the pipeline as a
message. `Command\CommandResult` and `Query\QueryResult` (see
[Commands &amp; Queries](./commands-and-queries.md)) are the concrete implementations used by this
library.

`StatusInterface` is an empty marker; `MessageStatus` is the built-in enum implementing it, with
`Success` and `Failure` cases.
