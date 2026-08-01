# message-bus Documentation (v1)

A command/query message bus for [Mezzio](https://docs.mezzio.dev/)/PSR-11 applications. A `MessageInterface`
(command or query) is handed to a `MessageBusInterface`, which runs it through a middleware pipeline. The
final middleware in the pipeline resolves and invokes the appropriate handler, and the result flows back out
through the pipeline as a `ResultInterface`.

```
Message -> MessageBus -> MiddlewarePipe -> [middleware...] -> MessageHandlerMiddleware -> Handler -> Result
```

## Contents

- [Message Bus &amp; Core Contracts](./message-bus.md) — `MessageBusInterface`, `MessageBus`, `MessageInterface`,
  `ResultInterface`, `StatusInterface`/`MessageStatus`, and handler resolution.
- [Middleware Pipeline](./middleware-pipeline.md) — `MiddlewarePipe`, `Next`, `MiddlewareInterface`, and the
  built-in `MessageHandlerMiddleware`/`EmptyPipelineHandler`.
- [Commands &amp; Queries](./commands-and-queries.md) — the `Command\*` and `Query\*` namespaces.
- [Configuration &amp; Container Integration](./configuration.md) — `ConfigProvider` and the PSR-11 factories.
- [Exceptions](./exceptions.md) — the exception types thrown by this library.
