# Commands &amp; Queries

## `Command\CommandInterface`

Marker interface extending `MessageInterface`. Application commands (write operations) implement this.

## `Command\NamedCommandInterface` / `Command\NamedCommandTrait`

`NamedCommandInterface` adds `getName(): string` to a command. `NamedCommandTrait` provides a default
implementation backed by a protected `$name` property, falling back to `static::class` when `$name`
isn't set.

## `Command\CommandResultInterface` / `Command\CommandResult`

`CommandResultInterface` extends both `CommandInterface` and `ResultInterface`, so a command result can
be treated as a message and re-enter the pipeline. `CommandResult` is the concrete, immutable
implementation, wrapping the original `CommandInterface`, a `MessageStatus`, and the raw result value.

## `Query\QueryInterface`

Marker interface extending `MessageInterface`. Application queries (read operations) implement this.

## `Query\QueryResultInterface` / `Query\QueryResult`

Mirrors the `Command` result types: `QueryResultInterface` extends `QueryInterface` and
`ResultInterface`, and `QueryResult` is the immutable implementation wrapping the original
`QueryInterface`, a `MessageStatus`, and the raw result value.
