# MessageHandlerInterface refactor (V2)

> **Status:** proposal for discussion. **Zero code changes** made.

## Problem

After the V2 split, `MessageHandlerInterface` has exactly one remaining role: the pipeline
continuation. But the name reads as "implement this to handle a message", which is the very
conflation V2 removes. A docblock cannot fix that, because the interface leaks into `@api`
signatures: consumers see the name in `MiddlewareInterface::process()` and on
`MessageBusInterface`, not the docblock.

## Current contract

`src/MessageHandlerInterface.php`, `Webware\MessageBus\MessageHandlerInterface`:

```php
/** @internal */
interface MessageHandlerInterface
{
    public function handle(MessageInterface $message): ResultInterface;
}
```

## Research findings, who references it after the V2 split

| File | FQCN | Usage |
| --- | --- | --- |
| `src/MiddlewareInterface.php` | `Webware\MessageBus\MiddlewareInterface` | `process()` second parameter type |
| `src/MessageBusInterface.php` | `Webware\MessageBus\MessageBusInterface` | `extends` |
| `src/MiddlewarePipelineInterface.php` | `Webware\MessageBus\MiddlewarePipelineInterface` | `extends` |
| `src/Next.php` | `Webware\MessageBus\Next` | `implements` |
| `src/Handler/EmptyPipelineHandler.php` | `Webware\MessageBus\Handler\EmptyPipelineHandler` | `implements` |
| `src/Middleware/MessageHandlerMiddleware.php` | `Webware\MessageBus\Middleware\MessageHandlerMiddleware` | `process()` second parameter type |

Two more references disappear under V2 and are therefore out of scope for the rename:

- `src/CommandHandlerInterface.php` / `src/QueryHandlerInterface.php`: become markers, no
  longer `extends MessageHandlerInterface`.
- `src/MessageHandlerResolverInterface.php` / `src/MessageHandlerResolver.php`: return the
  markers, no longer return `MessageHandlerInterface`.

## Recommendation

Rename to `PipelineHandlerInterface`.

Reasons:

1. The name states the contract: "the rest of the pipeline, callable through `handle()`."
2. It does not repeat the namespace (`Webware\MessageBus`).
3. `MessageBusInterface extends PipelineHandlerInterface` still reads naturally and preserves
   the useful property that a bus is itself composable inside another pipeline.

## Rejected alternatives

| Name | Why rejected |
| --- | --- |
| `HandlerInterface` | Too generic; still collides with the user-handler meaning |
| `NextHandlerInterface` | Collides with the existing `Next` class |
| `ContinuationInterface` | Precise but non-idiomatic for this domain |
| `RequestHandlerInterface` | Wrong domain (PSR-15 analogy; this is messages, not requests) |

## Can it be removed instead of renamed?

`handle()` cannot be folded into an existing interface cleanly:

- **`MiddlewareInterface`**: a middleware is not a handler. It implements only `process()`;
  adding `handle()` there would force every middleware to carry a dead method.
- **`MiddlewarePipelineInterface`**: `Next` and `EmptyPipelineHandler` are not pipelines.
  They implement only `handle()` and lack `pipe()`, so they cannot satisfy that interface.
  Yet `Next` is exactly what `MiddlewareInterface::process()` receives as its second
  argument, so that parameter's type must be satisfiable by a `handle()`-only class.

`Next` and `EmptyPipelineHandler` implement *only* `handle()`. That is the proof a minimal
continuation contract must exist: two concrete types whose entire contract is "continue and
return a result." You can rename that contract, but you cannot delete it.

The one removable edge is `MessageBusInterface extends MessageHandlerInterface`. The bus can
declare `handle()` directly instead of inheriting it:

```php
interface MessageBusInterface
{
    public function handle(MessageInterface $message): ResultInterface;
}
```

This removes one reference but does not delete the interface, `Next`,
`EmptyPipelineHandler`, and `MiddlewareInterface::process()` still need it. Keeping the
`extends` is worth it only if a bus should be usable as another pipeline's terminal handler;
the current code never does that, so dropping the `extends` is the tighter choice.

**Conclusion:** rename, do not delete. Optionally also drop `MessageBusInterface extends ...`
and declare `handle()` on the bus directly.

## Knock-on change: parameter name

`MiddlewareInterface::process()` and `MessageHandlerMiddleware::process()` use `$handler` for
the continuation parameter. That is the same conflation in parameter-name form. Rename the
parameter to `$next` alongside the interface rename:

```php
public function process(
    MessageInterface $message,
    PipelineHandlerInterface $next,
): ResultInterface;
```

## @api / @internal note

`MessageHandlerInterface` is marked `@internal`, but its *name* appears in `@api` signatures
(`MiddlewareInterface::process()`, `MessageBusInterface extends ...`). Renaming it is
therefore a de facto API break even though the interface itself is internal. This is exactly
why the rename belongs in the no-BC V2, and why it is the only real enforcement available:
the interface name is what consumers read, and a docblock does not constrain them.

## Mechanical scope

1. Rename `MessageHandlerInterface` to `PipelineHandlerInterface` (file + interface).
2. Update the six references in the table above.
3. Rename the `$handler` parameter to `$next` in `MiddlewareInterface::process()` and
   `MessageHandlerMiddleware::process()`.
4. No config, factory, or `MessageBusInterface` shape changes, only type/parameter names.
