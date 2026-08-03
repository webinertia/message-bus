# API Reference

Component-by-component reference for everything under `src/`. Interfaces are tagged in their docblocks
as `@api` (a public contract consumers implement/call directly) or `@internal` (wiring/glue that
consumers should not depend on directly).

- [Message Bus &amp; Core Contracts](./message-bus.md) — `MessageBusInterface`, `MessageBus`,
  `MessageInterface`, `ResultInterface`, `StatusInterface`/`MessageStatus`, and handler resolution.
- [Middleware Pipeline](./middleware-pipeline.md) — `MiddlewarePipe`, `Next`, `MiddlewareInterface`,
  and the built-in `MessageHandlerMiddleware`/`EmptyPipelineHandler`.
- [Commands &amp; Queries](./commands-and-queries.md) — the `Command\*` and `Query\*` namespaces.
- [Configuration &amp; Container Integration](./configuration.md) — `ConfigProvider` and the PSR-11
  factories, with code samples.
- [Exceptions](./exceptions.md) — the exception types thrown by this library.
