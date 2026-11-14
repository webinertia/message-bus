# Exceptions

All exceptions live under `Webware\MessageBus\Exception`.

| Exception | Extends / Implements | Thrown when |
|---|---|---|
| `InvalidConfigurationException` | `InvalidArgumentException` | Configuration is missing or malformed, a message has no `command_map`/`query_map` entry, or a resolved handler service doesn't implement `MessageHandlerInterface` (`MessageHandlerResolver`, `MiddlewarePipeFactory`). |
| `ServiceNotFoundException` | `RuntimeException`, `Psr\Container\NotFoundExceptionInterface` | A required service (`config`, a mapped handler, or `MiddlewarePipelineInterface`) isn't registered in the container (`MessageHandlerResolver`, `Container\MessageBusFactory`). |
| `MessageException` | `RuntimeException` | The pipeline reaches `Handler\EmptyPipelineHandler` without ever producing a `ResultInterface`. |
| `NextHandlerAlreadyCalledException` | `DomainException` | `Next::handle()` is invoked more than once on the same instance. |

Each exception exposes named static constructors (e.g. `ServiceNotFoundException::fromService()`,
`InvalidConfigurationException::fromUnMappedMessage()`) rather than public constructors, keeping error
messages consistent at every call site.
