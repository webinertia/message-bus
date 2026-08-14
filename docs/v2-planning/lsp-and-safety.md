# LSP and type safety (V2)

## Two contracts, two safety stories

V2 separates the pipeline contract from the resolved-handler contract.

### Pipeline side, fully LSP-checked

```php
interface PipelineHandlerInterface
{
    public function handle(MessageInterface $message): ResultInterface;
}
```

(renamed from `MessageHandlerInterface`; see
[MessageHandlerInterface refactor](./message-handler-interface-refactor.md)). This is a
declared, typed method. Implementations may narrow the return type (covariance) and widen
the parameter type (contravariance), never narrow the parameter. `Next`,
`EmptyPipelineHandler`, and `MiddlewarePipe` keep this contract, so the pipeline machinery
remains fully type-safe.

### Resolved-handler side, no forced method, runtime-enforced

```php
interface CommandHandlerInterface {}
interface QueryHandlerInterface {}
```

These markers declare no method, so there is nothing to be Liskov-checked. The dispatch
contract is `$resolved->{$method}($message)` with `$method` a runtime string. Every path,
including the default `'handle'`, is now enforced at runtime:

| Guarantee | Enforced by |
| --- | --- |
| Handler is a command/query handler | resolver `instanceof` marker check |
| Handler answers to the strategy's method name | middleware `is_callable` guard |
| Method returns a `ResultInterface` | `process(): ResultInterface` return type |
| Message satisfies the method's parameter type | the method's own parameter signature |

The last two are engine `TypeError`s unless the middleware opts into explicit wrapping.

## Why the method name is outside the type system

`ClassnameStrategy` derives `createUser` from `App\Command\CreateUser`. No interface can
declare `createUser` generically. The per-message method contract is a convention between:

1. the strategy (`createUser`),
2. the config map (`CreateUser::class => CreateUserHandler::class`),
3. the handler (`createUser(CreateUser $message): CommandResult`).

A mismatch surfaces as `HandlerMethodNotFoundException` or `TypeError`, not a compile-time
error.

## Regaining static typing (opt-in)

Handlers that want a checked dispatch can opt in, at their choice:

- **`__invoke`**: declare `__invoke(MessageInterface $message): ResultInterface`. It is
  declarable in an interface, so this path is fully LSP-checked, and the strategy returns
  `'__invoke'`.
- **A handle-bearing marker** (open decision): e.g.
  `HandleCommandHandlerInterface extends CommandHandlerInterface` declaring
  `handle(MessageInterface $message): ResultInterface`. This restores `#[Override]` and the
  static guarantee for handle-based handlers, at the cost of an extra interface.
- **A typed `__call`**: `__call(string $name, array $args): ResultInterface` makes any name
  callable with a runtime-enforced return type; the argument is `array` and must be asserted
  inside `__call`.

None of these is required. The marker contract stays method-free.

## Static-analysis gap

`$obj->{$var}(...)` is typed `mixed` by analyzers because the name is unknown, on every
path. This is the price of method-name-as-data; the optional contracts above are the way a
consumer buys static typing back.

