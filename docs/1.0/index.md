# message-bus Documentation (v1)

A command/query message bus for [Mezzio](https://docs.mezzio.dev/)/PSR-11 applications. A message
(command or query) is handed to a `MessageBusInterface`, which runs it through a middleware pipeline.
The final middleware in that pipeline resolves and invokes the appropriate handler, and the result
flows back out through the pipeline as a `ResultInterface`. See [Execution Flow](./flow-charts.md)
for diagrams of how a message moves through the pipeline and back.

## Contents

- [Execution Flow](./flow-charts.md): diagrams of message dispatch and result flow through the
  pipeline.
- [Getting Started](./getting-started.md): installation, requirements, and wiring the bus into your
  application.
- [Usage Examples](./usage-examples.md): defining commands/queries and handlers, dispatching
  messages, and writing custom middleware.
- [API Reference](./api-reference/index.md): what each class/interface does, grouped by area:
  - [Message Bus &amp; Core Contracts](./api-reference/message-bus.md)
  - [Middleware Pipeline](./api-reference/middleware-pipeline.md)
  - [Commands &amp; Queries](./api-reference/commands-and-queries.md)
  - [Configuration &amp; Container Integration](./api-reference/configuration.md)
  - [Exceptions](./api-reference/exceptions.md)
- [Benchmark Results](./benchmark-results.md): pipeline dispatch performance measured with PHPBench.
- [Contributing](./contributing.md): running tests, quality tooling, and CI expectations.
