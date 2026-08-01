# message-bus

A command/query message bus for [Mezzio](https://docs.mezzio.dev/) and other PSR-11 based applications.
Messages (commands or queries) are dispatched through a configurable middleware pipeline to their
mapped handler, and a result flows back out through that same pipeline.

## Installation

```bash
composer require webware/message-bus
```

Registers itself as a Laminas/Mezzio config provider (`Webware\MessageBus\ConfigProvider`) via
composer's `extra.laminas.config-provider`.

## Documentation

Full documentation lives in [docs/v1](./docs/v1/index.md):

- [Overview &amp; Architecture](./docs/v1/index.md)
- [Message Bus &amp; Core Contracts](./docs/v1/message-bus.md)
- [Middleware Pipeline](./docs/v1/middleware-pipeline.md)
- [Commands &amp; Queries](./docs/v1/commands-and-queries.md)
- [Configuration &amp; Container Integration](./docs/v1/configuration.md)
- [Exceptions](./docs/v1/exceptions.md)

## Testing

```bash
composer test              # unit tests
composer test-integration   # integration tests
composer test-all           # both
```

## License

BSD-3-Clause

