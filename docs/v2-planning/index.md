# Resolver + Strategy Collaborator — V2 (no backwards compatibility)

> **Status:** planning only. **Zero code changes** have been made to `src/` or `test/`.
> This is the V2 variation of the planning work in
> [`docs/resolver-strategy-collaborator`](../resolver-strategy-collaborator/index.md), with
> one constraint removed: **backwards compatibility is off the table.**

## Same goals, new freedom

The goal is unchanged: introduce a `StrategyInterface` collaborator that works in concert
with `MessageHandlerResolverInterface`, so `MessageHandlerMiddleware` moves from

```php
$result = $this->resolver->resolve($message)->handle($message);
```

to

```php
$result = $this->resolver->resolve($message)->{$this->strategy->match($message)};
```

Removing BC frees us to add the strategy seam, change the handler contracts, and redesign
the resolver return type. The chosen default still errs toward the **smallest upgrade
surface**: ship a `HandleStrategy` that returns `'handle'`, so handle-based handlers keep
working. The one thing V2 does **not** do is force `handle()` onto handlers that do not
need it.

## The strategy seam is the whole point

`StrategyInterface::match()` is the sole authority for choosing the method name. The
framework ships two strategies, and the application picks one purely by wiring:

- `HandleStrategy` (default) — returns `'handle'`. Today's behavior, behind a seam.
- `ClassnameStrategy` (opt-in) — inflects `App\Command\CreateUser` to `createUser`.

Because the middleware depends only on `StrategyInterface`, switching is a one-line
container alias change — no handler, resolver, or middleware change.

## What V2 settles

1. Strategy owns method-name derivation — `match()` has total control.
2. Shipped default: `HandleStrategy` (handle-based handlers keep working).
3. Shipped alternative: `ClassnameStrategy` (named methods), opt-in via wiring.
4. `CommandHandlerInterface`/`QueryHandlerInterface` become markers — no forced `handle()`.
5. `PipelineHandlerInterface::handle()` stays (renamed from `MessageHandlerInterface`), for
   pipeline machinery only.
6. Resolver return type changes to the markers.
7. Middleware gains the strategy dependency and a required `is_callable` guard.

## Contents

- [Architecture](./architecture.md) — contracts, shipped strategies, middleware, error surface.
- [PHP semantics](./php-semantics.md) — the verified language facts this design rests on.
- [LSP and type safety](./lsp-and-safety.md) — where guarantees live once `handle()` is no
  longer forced.
- [MessageHandlerInterface refactor](./message-handler-interface-refactor.md) — renaming the
  pipeline continuation contract.
- [Decisions](./decisions.md) — remaining open questions.
- [Refactor plan](./refactor-plan.md) — staged, verifiable plan to implement the 2.0 design.
- [Upgrade notes](./upgrade-notes.md) — consumer-facing 1.1.x → 2.0.0 breaking changes.

## Headline conclusions

- No forced `handle()`: a handler implements its marker and declares only the method its
  wired strategy needs.
- The default `HandleStrategy` keeps handle-based handlers working; their only change is
  dropping `#[Override]` on `handle()` (the marker no longer declares it).
- Dispatch is runtime-enforced on every path (`is_callable` + `process()` return type);
  static typing is available only by opt-in.
