# Commands &amp; Queries

## `Command\CommandInterface`

Marker interface extending `MessageInterface`. Application commands (write operations) implement this.

## `Command\NamedCommandInterface` / `Command\NamedCommandTrait`

`NamedCommandInterface` adds `getCommandName(): string` to a command. It declares no property: interface
properties are always public, so the contract stays method-only and implementors keep control of their
storage. `NamedCommandTrait` implements it with a `$commandName` property that defaults to the using class
name (late-bound, so it reports the concrete command class) and is writable only from inside the class.
The distinct name keeps it clear of a command's own `$name` property.

## `Command\CommandResultInterface` / `Command\CommandResult`

`CommandResultInterface` extends both `CommandInterface` and `ResultInterface`, adding
`getCommand(): CommandInterface`, so a command result can be treated as a message and re-enter the
pipeline. `CommandResult` is the concrete, `final readonly` implementation, wrapping the original
`CommandInterface`, a `MessageStatus`, and the raw result value.

## `Query\QueryInterface`

Marker interface extending `MessageInterface`. Application queries (read operations) implement this.

## `Query\QueryResultInterface` / `Query\QueryResult`

Mirrors the command result types: `QueryResultInterface` extends `QueryInterface` and
`ResultInterface`, adding `getQuery(): QueryInterface`. `QueryResult` is the `final readonly`
implementation wrapping the original `QueryInterface`, a `MessageStatus`, and the raw result value.

## Handler method naming

`CommandHandlerInterface`/`QueryHandlerInterface` declare no method, so each handler declares the
method its wired strategy expects:

- Under the default `HandleStrategy`, declare `handle(MessageInterface $message): ResultInterface` and
  narrow to the concrete message with `Type\instance_of()` or `instanceof`.
- Under `ClassnameStrategy`, declare a method named from the message's short class name
  (`CreateUser` -> `createUser`), typed directly to that class.

See [Strategy](./strategy.md) and [Usage Examples](../usage-examples.md).
