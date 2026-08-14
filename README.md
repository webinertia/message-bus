# message-bus

[![Continuous Integration](https://github.com/webinertia/message-bus/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/webinertia/message-bus/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/webinertia/message-bus/graph/badge.svg)](https://codecov.io/gh/webinertia/message-bus)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fwebinertia%2Fmessage-bus%2F1.1.x)](https://dashboard.stryker-mutator.io/reports/github.com/webinertia/message-bus/1.1.x)

A command/query message bus for [Mezzio](https://docs.mezzio.dev/) and other PSR-11 based applications.
Messages (commands or queries) are dispatched through a configurable middleware pipeline to their
mapped handler, and a result flows back out through that same pipeline.

## Installation

```bash
composer require webware/message-bus
```

Registers itself as a Laminas/Mezzio config provider (`Webware\MessageBus\ConfigProvider`) via
composer's `extra.laminas.config-provider`.

## Support

- Join our [Discord](https://discord.gg/89DfgmuF6C) to ask questions, report bugs, or discuss the library with the maintainers and other users. Please post support questions in the `#message-bus` channel.
- Report bugs or request features via [GitHub Issues](https://github.com/webinertia/message-bus/issues)

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

The middleware pipeline design in this package is directly inspired by, and
mirrors the mechanics of, the PSR-15-style pipeline used in [mezzio/mezzio](https://github.com/mezzio/mezzio).

This package was built for the Laminas/Mezzio ecosystem and its users, and that debt is gladly owed,
credit belongs with the Laminas/Mezzio maintainers and contributors for the original design. See
[mezzio/mezzio](https://github.com/mezzio/mezzio) and its
[LICENSE](https://github.com/mezzio/mezzio/blob/master/LICENSE.md) (BSD-3-Clause) for the source of that
inspiration.

## License

BSD-3-Clause
