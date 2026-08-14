# Upgrade notes, 1.1.x → 2.0.0

Consumer-facing breaking changes introduced by the resolver + strategy refactor.

## Handler contracts

- `Webware\MessageBus\CommandHandlerInterface` and `Webware\MessageBus\QueryHandlerInterface`
  are now **markers**, they no longer declare `handle()`. A handler implements the marker and
  declares only the method its wired strategy needs.
- Existing handle-based handlers keep working under the default strategy, with one change:
  remove `#[Override]` from `handle()`, because it no longer overrides an interface method.
  Method body and signature are unchanged.
- Named-method handlers (opt-in `ClassnameStrategy`) declare their own method, e.g.
  `createUser(CreateUser $message): CommandResult`, and no `handle()`.

## Renamed interface

- `Webware\MessageBus\MessageHandlerInterface` → `Webware\MessageBus\PipelineHandlerInterface`.
  It is now the pipeline continuation contract only (`handle(MessageInterface $message):
  ResultInterface`). Consumers who type-hinted it (e.g. in custom middleware `process()`) must
  update the name and, if desired, the parameter name `$handler` → `$next`.

## Resolver

- `Webware\MessageBus\MessageHandlerResolverInterface::resolve()` now returns
  `CommandHandlerInterface|QueryHandlerInterface` instead of `MessageHandlerInterface`.

## Middleware + strategy wiring

- `Webware\MessageBus\Middleware\MessageHandlerMiddleware::__construct()` now takes a second
  argument: `StrategyInterface $strategy`. It is wired by the container, not constructed manually
  in typical use.
- `Webware\MessageBus\StrategyInterface` is new (`@api`), with `handlerMethod(MessageInterface $message):
  string`. Two implementations ship:
  - `Webware\MessageBus\Strategy\HandleStrategy` (default): returns `'handle'`.
  - `Webware\MessageBus\Strategy\ClassnameStrategy` (opt-in): inflects the message short class
    name, `lcfirst` (`CreateUser` → `createUser`).
- To opt into named methods, alias `StrategyInterface::class` to `ClassnameStrategy::class` in
  your container config. The default `ConfigProvider` aliases it to `HandleStrategy`.

## Error surface

- `Webware\MessageBus\Exception\HandlerMethodNotFoundException` is thrown when the resolved
  handler has no callable method for the strategy's name.
- A handler method that returns a non-`ResultInterface` value now raises a `TypeError` with a
  descriptive message.

## Backwards-compatibility report

`roave/backward-compatibility-check` detected the latest release as `1.0.0` and compared it to
the working tree; it reported no breaks, because the tool skips `@internal` symbols and the
comparison base was the `1.0.0` tag rather than the unreleased `1.1.x`. The authoritative
breaking-change list is the table above, written against the `1.1.x` contract set.
