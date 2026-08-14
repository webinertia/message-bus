# Decisions (V2)

## Settled (direction)

1. Strategy owns method-name derivation, `handlerMethod()` has total control.
2. Shipped default: `HandleStrategy` (`'handle'`).
3. Shipped alternative: `ClassnameStrategy` (inflect from short class name, `lcfirst`),
   opt-in by changing one container alias.
4. `CommandHandlerInterface`/`QueryHandlerInterface` become markers, no forced `handle()`.
5. `PipelineHandlerInterface::handle()` stays, for pipeline machinery only (renamed from
   `MessageHandlerInterface`).
6. Resolver return type changes to the markers
   (`CommandHandlerInterface|QueryHandlerInterface`).
7. Middleware gains the strategy dependency and a required `is_callable` guard.

## Open

1. **`#[Override]` migration for handle-based handlers.** The marker no longer declares
   `handle()`, so existing handlers must drop the `#[Override]` attribute. Alternative: ship
   optional `HandleCommandHandlerInterface`/`HandleQueryHandlerInterface` that declare
   `handle()`, preserving `#[Override]` and static typing for those who want it. Which?
2. **Inflection rule details.** Short name + `lcfirst` (`CreateUser` → `createUser`), a
   `handle` prefix (`handleCreateUser`), or `__invoke` when declared? Strategy-internal.
3. **Error wrapping.** Convert `TypeError`/`ArgumentCountError` into a library exception
   (e.g. `HandlerInvocationException`), or let them propagate?
4. **`__call` support.** The `is_callable` guard already admits `__call`-backed handlers.
   Document as first-class, or require real methods?
5. **Namespace for strategies.** `Webware\MessageBus\Strategy\HandleStrategy` /
   `ClassnameStrategy` (new namespace) vs top-level.
6. **`NamedCommandInterface` interplay.** A named command's `getName()` could be an
   alternative derivation source. Second shipped strategy or out of scope?
7. **Result re-dispatch.** `ResultInterface` is a `MessageInterface`;
   `ClassnameStrategy` would inflect `CommandResult` → `commandResult`. State the strategy
   contract for that case (or document that results must not be re-dispatched through a
   classname strategy).
8. **`MessageBusInterface` inheritance.** Extend `PipelineHandlerInterface` (a bus usable as
   another pipeline's terminal handler) or declare `handle()` directly on the bus? The
   current code never nests buses, so dropping the `extends` is tighter.
