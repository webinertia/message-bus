---
applyTo: "test/**/*.php"
---

# Copilot Instructions: PHPUnit Test Generation for message-bus

## Project context

- Library under test: `Webware\MessageBus\*` (`src/`) — a command/query message bus with a middleware
  pipeline (`MiddlewarePipe`/`Next`) that resolves handlers via `MessageHandlerResolver`, wired through
  PSR-11 factories in `Container/`.
- Tests live in `Webware\MessageBusTest\*` (`test/unit/`) and `Webware\MessageBusIntegrationTest\*`
  (`test/integration/`, real container wiring — see [LaminasServiceManagerMessageBusTest.php](../../test/integration/LaminasServiceManagerMessageBusTest.php)).
- PHP: `~8.4.1 || ~8.5.0`. PHPUnit: `^12.5.30`.
- Quality tooling is **Mago** (`mago.toml`) — format, lint, and static analysis. There is no
  PHPStan/PHP_CodeSniffer/phpcs in this project.

## Running tests & quality checks

```bash
composer test              # unit tests only (test/unit)
composer test-integration   # integration tests only (test/integration)
composer test-all           # both suites
composer test-coverage      # unit tests + coverage-clover clover.xml

mago format --check         # formatting check
mago lint                   # lint rules
mago analyze                # static analysis
```

CI ([continuous-integration.yml](../workflows/continuous-integration.yml)) runs `mago format --check`,
`mago lint`, `mago analyze`, then the full PHPUnit suite across PHP 8.4/8.5 with lowest/locked/latest
dependency sets — new tests must pass under all of those.

## Test file conventions

- One test class per source class: `{TargetClass}Test`, under the mirrored path in `test/unit/`.
- `declare(strict_types=1);`, `final class ... extends TestCase`.
- Annotate with `#[CoversClass(TargetClass::class)]`, and optionally `#[CoversMethod(TargetClass::class, 'method')]`
  per method under focus (see [MessageBusTest.php](../../test/unit/MessageBusTest.php)).
- **Use the `#[Test]` attribute on test methods, never a `test`-prefixed name.** Method names are plain
  camelCase descriptions of the scenario, e.g. `resolveThrowsServiceNotFoundExceptionWhenHandlerIsNotInContainer()`,
  `factoryCanBeInvokedMultipleTimes()`. Mago's `prefer-test-attribute` rule enforces this.
- Use `static::assert*()`, not `$this->assert*()`.
- `#[Override]` on `setUp()`/`tearDown()` and any overridden interface method.
- **Doubles**: use `$this->createStub(SomeInterface::class)` when you only need a canned return value
  with no call-count expectations (the overwhelming majority of doubles in this suite). Reserve
  `$this->createMock(...)` for cases where you genuinely need to assert *how* a dependency was called
  (`->expects($this->once())->method(...)->with(...)`).
- For `final readonly` classes (most of `src/`), construct real instances — they can't be mocked/stubbed.
- Use anonymous classes implementing the relevant interface (`CommandInterface`, `MessageHandlerInterface`,
  etc.) as lightweight test doubles for value objects/messages instead of mocking them.
- Prefer `#[TestWith([...])]` for parameterized cases over data provider methods (see `MessageBusTest.php`).
- Reuse `test/unit/TestAssets/InMemoryContainer.php` for tests needing a real PSR-11 container instead
  of a stub.

## Example skeleton

```php
<?php

declare(strict_types=1);

namespace Webware\MessageBusTest;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\TargetClass;

#[CoversClass(TargetClass::class)]
final class TargetClassTest extends TestCase
{
    private TargetClass $subject;

    #[Test]
    public function handleReturnsExpectedResult(): void
    {
        static::assertSame('expected', $this->subject->handle(/* ... */));
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new TargetClass();
    }
}
```

## Mago rules that affect test code

- Yoda conditions (`null === $value`, `[] === $items`).
- camelCase method/variable names.
- No fully-qualified class references inline — always `use` them.
- If a lint rule is a deliberate false positive on one line, suppress it with
  `// @mago-expect lint:rule-name` immediately above that line — don't disable the rule globally.

## Current directory structure

```
test/
├── integration/
│   ├── LaminasServiceManagerMessageBusTest.php
│   └── TestAssets/
│       ├── Command.php
│       ├── CommandHandler.php
│       ├── TestMiddlewareFirst.php
│       └── TestMiddlewareSecond.php
└── unit/
    ├── ConfigProviderTest.php
    ├── MessageBusTest.php
    ├── MessageHandlerResolverTest.php
    ├── MiddlewarePipeTest.php
    ├── NextTest.php
    ├── Container/
    │   ├── MessageBusFactoryTest.php
    │   ├── MessageHandlerMiddlewareFactoryTest.php
    │   ├── MessageHandlerResolverFactoryTest.php
    │   └── MiddlewarePipeFactoryTest.php
    ├── Handler/
    │   └── EmptyPipelineHandlerTest.php
    ├── Middleware/
    │   └── MessageHandlerMiddlewareTest.php
    └── TestAssets/
        ├── ExpectedConfig.php
        └── InMemoryContainer.php
```

## What to cover per class

1. Interface/contract compliance where relevant.
2. Constructor wiring / factory happy path.
3. Core behavior (the actual `handle()`/`process()`/`resolve()` logic).
4. Every documented `@throws` — exception type **and** message where the message is meaningful (see
   the `Exception\*::from*()` factory methods).
5. Boundary cases the class can actually reach (empty pipeline, unmapped message, missing container
   service, etc.) — don't invent scenarios it can't hit.

Keep tests scoped to what the class actually does — this library favors small, focused test classes
over exhaustive parameterized suites.
