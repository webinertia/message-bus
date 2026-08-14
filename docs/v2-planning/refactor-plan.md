# Refactor plan, message-bus 2.0.0 (branch `2.0.x`)

> Status: plan only. No `src/` or `test/` changes have been made. This plan turns the
> settled V2 design ([`architecture.md`](./architecture.md), [`decisions.md`](./decisions.md),
> [`message-handler-interface-refactor.md`](./message-handler-interface-refactor.md)) into an
> ordered, staged refactor where every stage is verifiable by the existing test suites.

## Scope

Implement the V2 design end-to-end:

- `StrategyInterface` + shipped `HandleStrategy` (default) and `ClassnameStrategy` (opt-in).
- `CommandHandlerInterface` / `QueryHandlerInterface` become markers (no forced `handle()`).
- `MessageHandlerInterface` renamed to `PipelineHandlerInterface` (pipeline machinery only).
- Resolver returns the markers; middleware dispatches through `$strategy->handlerMethod($message)`.
- New `HandlerMethodNotFoundException`.
- Container wiring for the strategy.

Out of scope for this refactor (recorded open decisions, see below): no optional
`HandleCommandHandlerInterface`/`HandleQueryHandlerInterface` interfaces, no
`HandlerInvocationException` wrapping, no `NamedCommandInterface` strategy.

## Verification command matrix

| Command | Verifies |
| --- | --- |
| `composer test` | unit suite (`test/unit`) |
| `composer test-integration` | integration suite (`test/integration`) |
| `composer test-all` | both suites |
| `mago format --check src test` | formatting |
| `mago lint src test --reporting-format=short` | lint |
| `mago analyze src --reporting-format=short` | static analysis (config excludes `test`) |
| `composer mutation-test` | mutation score (Infection) |
| `composer benchmark` | pipeline/dispatch performance (PHPBench) |
| `vendor/bin/roave-backward-compatibility-check` | **expected to report breaks**, documentation only, not a gate |

Baseline rule: every stage must end green (`composer test-all` + the mago commands for that
stage) before the next stage begins. One commit per stage keeps each verification point
bisectable.

## Open decisions and their defaults for this plan

| # | Decision | Default adopted here |
| --- | --- | --- |
| 1 | `#[Override]` migration for handle-based handlers | Markers only; handlers drop `#[Override]` on `handle()`. No `Handle*` interfaces. |
| 2 | Inflection rule | Short class name + `lcfirst` (`CreateUser` → `createUser`). |
| 3 | Error wrapping beyond the guard | Only `HandlerMethodNotFoundException`; let `TypeError`/`ArgumentCountError` propagate. |
| 4 | `__call` support | Supported implicitly by the `is_callable` guard; not required or specially tested. |
| 5 | Strategy namespace | `Webware\MessageBus\Strategy\`. |
| 6 | `NamedCommandInterface` strategy | Out of scope. |
| 7 | Result re-dispatch | Document only; strategy is never consulted for a `ResultInterface` in normal flow. |
| 8 | `MessageBusInterface` inheritance | Keep `extends PipelineHandlerInterface` (pure rename). Dropping the `extends` is a follow-up. |

---

## Stage 0, Baseline

**Purpose:** establish a green starting point.

**Changes:** none.

**Verification:**

- [ ] `git status` clean on `2.0.x`
- [ ] `composer test-all`
- [ ] `mago format --check src test`
- [ ] `mago lint src test --reporting-format=short`
- [ ] `mago analyze src --reporting-format=short`

All must pass before any refactor step.

---

## Stage 1, Add the strategy contracts (additive, no behavior change)

**Purpose:** introduce `StrategyInterface` and the two shipped strategies with unit tests.
Nothing is wired yet, so existing behavior is untouched.

**Changes, `src/`:**

- `src/StrategyInterface.php`: `Webware\MessageBus\StrategyInterface` (`@api`):
  `public function handlerMethod(MessageInterface $message): string;`
- `src/Strategy/HandleStrategy.php`: `Webware\MessageBus\Strategy\HandleStrategy`
  (`final readonly`, `#[Override]` on `handlerMethod()`), returns `'handle'`.
- `src/Strategy/ClassnameStrategy.php`: `Webware\MessageBus\Strategy\ClassnameStrategy`
  (`final readonly`, `#[Override]` on `handlerMethod()`), returns `lcfirst(short class name)`.
  Import functions: `use function lcfirst; use function strrpos; use function substr;`
  (mago `no-fully-qualified-global-function`).

**Changes, `test/`:**

- `test/unit/TestAssets/CreateUser.php`: fixture message
  `Webware\MessageBusTest\TestAssets\CreateUser implements CommandInterface` (empty class).
- `test/unit/Strategy/HandleStrategyTest.php`: `Webware\MessageBusTest\Strategy\HandleStrategyTest`:
  `handlerMethod()` returns `'handle'` for a `MessageInterface` stub.
- `test/unit/Strategy/ClassnameStrategyTest.php`:
  `Webware\MessageBusTest\Strategy\ClassnameStrategyTest`: `handlerMethod(new CreateUser()) === 'createUser'`;
  covers namespaced short-name inflection.

**Verification:**

- [ ] `composer test`
- [ ] `mago format --check src test`
- [ ] `mago lint src test --reporting-format=short`
- [ ] `mago analyze src --reporting-format=short`

---

## Stage 2, Rename `MessageHandlerInterface` → `PipelineHandlerInterface`

**Purpose:** mechanical rename of the pipeline continuation contract. No behavior change.

**Changes, `src/`:**

- Rename `src/MessageHandlerInterface.php` → `src/PipelineHandlerInterface.php`;
  interface `Webware\MessageBus\PipelineHandlerInterface` (`@internal`), method unchanged.
- Update references:
  - `src/MiddlewareInterface.php`: `process(MessageInterface $message, PipelineHandlerInterface $handler)`
    (param *name* `$handler` stays until Stage 3).
  - `src/MessageBusInterface.php`: `extends PipelineHandlerInterface` (decision #8 default).
  - `src/MiddlewarePipelineInterface.php`: `extends MiddlewareInterface, PipelineHandlerInterface`.
  - `src/Next.php`: `implements PipelineHandlerInterface`; promoted property type.
  - `src/Handler/EmptyPipelineHandler.php`: `implements PipelineHandlerInterface`.
  - `src/Middleware/MessageHandlerMiddleware.php`: `process()` second parameter type.

**Changes, `test/` (grep `MessageHandlerInterface` and update every hit):**

- `test/unit/Middleware/MessageHandlerMiddlewareTest.php`: property types,
  `createHandler()` anonymous class `implements PipelineHandlerInterface`.
- `test/unit/MessageBusTest.php`: anonymous middleware `process()` parameter types.
- `test/unit/MiddlewarePipeTest.php`: `assertInstanceOf`, `createStub`/`createMock`,
  anonymous middleware parameter types.
- `test/unit/Handler/EmptyPipelineHandlerTest.php`: `assertInstanceOf`.
- `test/integration/TestAssets/TestMiddlewareFirst.php` / `TestMiddlewareSecond.php`:
  `process()` parameter type.

**Verification:**

- [ ] `composer test`
- [ ] `composer test-integration`
- [ ] `mago lint src test --reporting-format=short`
- [ ] `mago analyze src --reporting-format=short`

---

## Stage 3, Rename the continuation parameter `$handler` → `$next`

**Purpose:** finish the name cleanup from
[`message-handler-interface-refactor.md`](./message-handler-interface-refactor.md).

**Changes, `src/`:**

- `src/MiddlewareInterface.php`: parameter name `$next`.
- `src/Middleware/MessageHandlerMiddleware.php`: parameter name and body usage `$next->handle($result)`.

**Changes, `test/` (cosmetic, optional but recommended for consistency):**

- Test middleware fixtures in `test/unit/MessageBusTest.php`, `test/unit/MiddlewarePipeTest.php`,
  `test/integration/TestAssets/TestMiddlewareFirst.php`, `TestMiddlewareSecond.php`,
  rename the local `$handler` parameter to `$next`. PHP does not require parameter-name
  parity, so this is cosmetic.

**Verification:**

- [ ] `composer test`
- [ ] `composer test-integration`

---

## Stage 4, Command/query handlers become markers; resolver return type changes

**Purpose:** remove the forced `handle()` from resolved handlers and retype the resolver.

**Changes, `src/`:**

- `src/CommandHandlerInterface.php`: empty marker `Webware\MessageBus\CommandHandlerInterface`
  (`@api`); remove `extends MessageHandlerInterface`, the `handle()` re-declaration, and `use Override;`.
- `src/QueryHandlerInterface.php`: same for `Webware\MessageBus\QueryHandlerInterface`.
- `src/MessageHandlerResolverInterface.php`: `resolve(MessageInterface $message): CommandHandlerInterface|QueryHandlerInterface;`
- `src/MessageHandlerResolver.php`: update `resolve()` and `__invoke()` return types to the
  union; keep the `instanceof` validation against `QueryHandlerInterface::class` /
  `CommandHandlerInterface::class`; keep the existing `@throws` docblock.

**Changes, `test/`:**

- `test/unit/MessageHandlerResolverTest.php`: in `createCommandHandler()` /
  `createQueryHandler()`: remove `#[Override]` from `handle()` (and `use Override;` if now
  unused). `handle()` remains as a plain method because the tests still call
  `$resolved->handle($message)` directly.
- `test/integration/TestAssets/CommandHandler.php`: remove `#[Override]` and `use Override;`;
  keep `handle()`.

**Verification (watch `check-missing-override` and `check-throws`):**

- [ ] `composer test`
- [ ] `composer test-integration`
- [ ] `mago lint src test --reporting-format=short`
- [ ] `mago analyze src --reporting-format=short`

---

## Stage 5, Add `HandlerMethodNotFoundException`

**Purpose:** the library exception for "resolved handler has no callable method for the
strategy's name."

**Changes, `src/`:**

- `src/Exception/HandlerMethodNotFoundException.php`:
  `Webware\MessageBus\Exception\HandlerMethodNotFoundException extends InvalidArgumentException`
  with a static factory
  `forMethod(CommandHandlerInterface|QueryHandlerInterface $handler, string $method): self`
  whose message includes `$handler::class` and `$method`.

**Changes, `test/`:**

- `test/unit/Exception/HandlerMethodNotFoundExceptionTest.php`: asserts message contains the
  handler class and method name, and that the exception is an `InvalidArgumentException`.

**Verification:**

- [ ] `composer test`
- [ ] `mago lint src test --reporting-format=short`

---

## Stage 6, Wire the strategy and switch middleware to dynamic dispatch

**Purpose:** the behavioral core, the target call site.

**Changes, `src/`:**

- `src/Middleware/MessageHandlerMiddleware.php`:
  - constructor gains `private StrategyInterface $strategy` (after the resolver).
  - `process()`:
    ```php
    $resolved = $this->resolver->resolve($message);
    $method   = $this->strategy->handlerMethod($message);

    if (! is_callable([$resolved, $method])) {
        throw HandlerMethodNotFoundException::forMethod($resolved, $method);
    }

    $result = $resolved->{$method}($message);

    return $next->handle($result);
    ```
  - document `@throws HandlerMethodNotFoundException` (mago `check-throws`).
- `src/Container/MessageHandlerMiddlewareFactory.php`: fetch `StrategyInterface::class` and
  pass it to the middleware constructor.
- `src/ConfigProvider.php`:
  - aliases: `StrategyInterface::class => Strategy\HandleStrategy::class`.
  - invokables: `Strategy\HandleStrategy::class`, `Strategy\ClassnameStrategy::class`.

**Changes, `test/`:**

- `test/unit/Middleware/MessageHandlerMiddlewareTest.php`:
  - construct the middleware with a strategy (`new HandleStrategy()` or a stub).
  - add: `processCallsStrategyWithCorrectMessage()` (strategy mock `handlerMethod()` called once with
    the message).
  - add: `processThrowsHandlerMethodNotFoundExceptionWhenMethodMissing()` (strategy returns a
    name the resolved handler lacks).
  - existing resolve/result tests updated for the new constructor.
- `test/unit/Container/MessageHandlerMiddlewareFactoryTest.php`: container mock returns a
  `StrategyInterface` for `get(StrategyInterface::class)`; assert the factory passes it.
- `test/unit/ConfigProviderTest.php` and `test/unit/TestAssets/ExpectedConfig.php`: add the
  expected alias and two invokables.

**Verification:**

- [ ] `composer test`
- [ ] `composer test-integration`
- [ ] `mago format --check src test`
- [ ] `mago lint src test --reporting-format=short`
- [ ] `mago analyze src --reporting-format=short`

---

## Stage 7, Integration coverage for both strategies

**Purpose:** prove the default (`HandleStrategy`) and opt-in (`ClassnameStrategy`) paths
end-to-end through the Laminas ServiceManager.

**Changes, `test/`:**

- Default path is already covered: `test/integration/LaminasServiceManagerMessageBusTest.php`
  (handle-based `CommandHandler` + `HandleStrategy` default). Confirm it passes unchanged.
- Opt-in path:
  - `test/integration/TestAssets/NamedCommand.php`: `Webware\MessageBusIntegrationTest\TestAssets\NamedCommand`
    implements `CommandInterface`.
  - `test/integration/TestAssets/NamedCommandHandler.php`: implements `CommandHandlerInterface`
    with `public function namedCommand(NamedCommand $message): CommandResult` (no `handle()`).
  - `test/integration/LaminasClassnameStrategyMessageBusTest.php`: builds a `ServiceManager`
    from `ConfigProvider`, overrides
    `$dependencies['aliases'][StrategyInterface::class] = ClassnameStrategy::class`, maps
    `NamedCommand::class => NamedCommandHandler::class`, dispatches, and asserts the result.

**Verification:**

- [ ] `composer test-integration`
- [ ] `composer test-all`
- [ ] `mago lint src test --reporting-format=short`

---

## Stage 8, Format, lint, analyze pass

**Purpose:** bring the tree to a clean static-analysis state.

**Changes:** run and fix.

- `mago format src test` (then re-run `--check`). Note `sort-class-methods = true` may reorder
  methods (e.g. `__invoke`), expected, not data loss.
- Fix all `mago lint` and `mago analyze` findings.
- Do **not** add `@mago-expect` suppressions without explicit approval (project rule).

**Verification:**

- [ ] `mago format --check src test`
- [ ] `mago lint src test --reporting-format=short`
- [ ] `mago analyze src --reporting-format=short`

---

## Stage 9, Mutation testing and benchmarks

**Purpose:** prove the new branches are covered and the dynamic dispatch did not regress
performance.

**Changes:** none expected; add tests only if Infection flags uncovered mutations in the new
code (notably the `is_callable` guard, the exception factory, and both strategies).

**Verification:**

- [ ] `composer mutation-test`: MSI above the configured threshold in `infection.json5.dist`.
- [ ] `composer benchmark`: compare `MiddlewarePipeBench` / `MessageBusDispatchBench` output
      to `docs/v1/benchmark-results.md`; record any delta (expected negligible).

---

## Stage 10, Documentation and BC report

**Purpose:** finalize docs and capture the breaking-change surface.

**Changes:**

- Move planning docs out of `docs/v2-planning/` to their final home and/or update
  `docs/` indexes to reference the 2.0 design (confirm location with maintainer).
- Add an upgrade note summarizing the consumer-facing changes: markers replace
  `handle()`-bearing handler interfaces, `MessageHandlerInterface` → `PipelineHandlerInterface`,
  resolver return type change, strategy wiring.
- Run `vendor/bin/roave-backward-compatibility-check` to generate the breaking-change list;
  store it as documentation (this is a no-BC 2.0, so it is expected to report breaks).

**Verification:**

- [ ] `composer test-all`
- [ ] `mago format --check src test`
- [ ] `mago lint src test --reporting-format=short`
- [ ] `mago analyze src --reporting-format=short`
- [ ] `composer mutation-test` still green
- [ ] BC report captured

---

## File manifest (end state)

### New, `src/`

| File | FQCN |
| --- | --- |
| `src/StrategyInterface.php` | `Webware\MessageBus\StrategyInterface` |
| `src/Strategy/HandleStrategy.php` | `Webware\MessageBus\Strategy\HandleStrategy` |
| `src/Strategy/ClassnameStrategy.php` | `Webware\MessageBus\Strategy\ClassnameStrategy` |
| `src/Exception/HandlerMethodNotFoundException.php` | `Webware\MessageBus\Exception\HandlerMethodNotFoundException` |
| `src/PipelineHandlerInterface.php` (renamed) | `Webware\MessageBus\PipelineHandlerInterface` |

### Changed, `src/`

| File | FQCN |
| --- | --- |
| `src/CommandHandlerInterface.php` | `Webware\MessageBus\CommandHandlerInterface` (marker) |
| `src/QueryHandlerInterface.php` | `Webware\MessageBus\QueryHandlerInterface` (marker) |
| `src/MessageHandlerResolverInterface.php` | `Webware\MessageBus\MessageHandlerResolverInterface` |
| `src/MessageHandlerResolver.php` | `Webware\MessageBus\MessageHandlerResolver` |
| `src/MiddlewareInterface.php` | `Webware\MessageBus\MiddlewareInterface` |
| `src/Middleware/MessageHandlerMiddleware.php` | `Webware\MessageBus\Middleware\MessageHandlerMiddleware` |
| `src/MessageBusInterface.php` | `Webware\MessageBus\MessageBusInterface` |
| `src/MiddlewarePipelineInterface.php` | `Webware\MessageBus\MiddlewarePipelineInterface` |
| `src/Next.php` | `Webware\MessageBus\Next` |
| `src/Handler/EmptyPipelineHandler.php` | `Webware\MessageBus\Handler\EmptyPipelineHandler` |
| `src/Container/MessageHandlerMiddlewareFactory.php` | `Webware\MessageBus\Container\MessageHandlerMiddlewareFactory` |
| `src/ConfigProvider.php` | `Webware\MessageBus\ConfigProvider` |

### New, `test/`

| File | FQCN |
| --- | --- |
| `test/unit/Strategy/HandleStrategyTest.php` | `Webware\MessageBusTest\Strategy\HandleStrategyTest` |
| `test/unit/Strategy/ClassnameStrategyTest.php` | `Webware\MessageBusTest\Strategy\ClassnameStrategyTest` |
| `test/unit/Exception/HandlerMethodNotFoundExceptionTest.php` | `Webware\MessageBusTest\Exception\HandlerMethodNotFoundExceptionTest` |
| `test/unit/TestAssets/CreateUser.php` | `Webware\MessageBusTest\TestAssets\CreateUser` |
| `test/integration/TestAssets/NamedCommand.php` | `Webware\MessageBusIntegrationTest\TestAssets\NamedCommand` |
| `test/integration/TestAssets/NamedCommandHandler.php` | `Webware\MessageBusIntegrationTest\TestAssets\NamedCommandHandler` |
| `test/integration/LaminasClassnameStrategyMessageBusTest.php` | `Webware\MessageBusIntegrationTest\LaminasClassnameStrategyMessageBusTest` |

### Changed, `test/`

| File |
| --- |
| `test/unit/Middleware/MessageHandlerMiddlewareTest.php` |
| `test/unit/Container/MessageHandlerMiddlewareFactoryTest.php` |
| `test/unit/ConfigProviderTest.php` |
| `test/unit/TestAssets/ExpectedConfig.php` |
| `test/unit/MessageHandlerResolverTest.php` |
| `test/unit/MessageBusTest.php` |
| `test/unit/MiddlewarePipeTest.php` |
| `test/unit/Handler/EmptyPipelineHandlerTest.php` |
| `test/integration/TestAssets/CommandHandler.php` |
| `test/integration/TestAssets/TestMiddlewareFirst.php` |
| `test/integration/TestAssets/TestMiddlewareSecond.php` |
