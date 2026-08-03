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
- [Getting Started](./docs/v1/getting-started.md)
- [Usage Examples](./docs/v1/usage-examples.md)
- [API Reference](./docs/v1/api-reference/index.md)
- [Contributing](./docs/v1/contributing.md)

## Testing

```bash
composer test              # unit tests
composer test-integration  # integration tests
composer test-all          # both
```

## Acknowledgments

The middleware pipeline design in this package (`MiddlewarePipe`/`Next`) is directly inspired by, and
mirrors the mechanics of, the PSR-15-style pipeline used in [mezzio/mezzio](https://github.com/mezzio/mezzio).
Two small container helper functions (`collection_mapper_factory()` and
`priority_queue_reducer_factory()`, used to build a prioritized middleware queue from configuration)
were originally adapted from Mezzio's own pipeline-building code.

This package was built for the Laminas/Mezzio ecosystem and its users, and that debt is gladly owed —
credit belongs with the Laminas/Mezzio maintainers and contributors for the original design. See
[mezzio/mezzio](https://github.com/mezzio/mezzio) and its
[LICENSE](https://github.com/mezzio/mezzio/blob/master/LICENSE.md) (BSD-3-Clause) for the source of that
inspiration.

## License

BSD-3-Clause
