# Commands &amp; Queries

## `Command\CommandInterface`

Marker interface extending `MessageInterface`. Application commands (write operations) implement this.

## `Command\NamedCommandInterface` / `Command\NamedCommandTrait`

`NamedCommandInterface` adds `getName(): string` to a command. `NamedCommandTrait` provides a default
implementation backed by a protected `$name` property, falling back to `static::class` when `$name`
isn't set.

## `Command\CommandResultInterface` / `Command\CommandResult`

`CommandResultInterface` extends both `CommandInterface` and `ResultInterface`, so a command result can
be treated as a message and re-enter the pipeline. `CommandResult` is the concrete, `final readonly`
implementation, wrapping the original `CommandInterface`, a `MessageStatus`, and the raw result value.

## `Query\QueryInterface`

Marker interface extending `MessageInterface`. Application queries (read operations) implement this.

## `Query\QueryResultInterface` / `Query\QueryResult`

Mirrors the `Command` result types: `QueryResultInterface` extends `QueryInterface` and
`ResultInterface`, and `QueryResult` is the `final readonly` implementation wrapping the original
`QueryInterface`, a `MessageStatus`, and the raw result value.

## Narrowing a handler's incoming message

`CommandHandlerInterface`/`QueryHandlerInterface::handle()` is typed to the broad `MessageInterface`.
Parameter types are contravariant, so an implementation can widen but never narrow that type, however
declaring it as a union such as `MessageInterface|CommandInterface` (or `MessageInterface|QueryInterface`)
is still a legal override, since the union is at least as wide as `MessageInterface` alone. This costs
nothing at runtime but gives static analysis (and readers) `CommandInterface`/`QueryInterface` typing
without an assertion.

To narrow further, down to the concrete command/query class, this library's own tests use
[`Psl\Type::instance_of()`](https://github.com/php-standard-library/type), see
[Usage Examples](../usage-examples.md#defining-a-command-and-its-handler) for a complete example.
