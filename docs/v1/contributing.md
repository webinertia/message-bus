# Contributing

## Running tests

```bash
composer test              # unit tests only (test/unit)
composer test-integration   # integration tests only (test/integration)
composer test-all           # both suites
composer test-coverage      # unit tests + coverage-clover clover.xml
```

## Benchmarks

```bash
composer benchmark          # phpbench run --report=aggregate
```

Benchmarks live under `benchmarks/` (config in `phpbench.json.dist`) and measure pipeline dispatch
performance — see [Benchmark Results](./benchmark-results.md) for the latest numbers. This directory
is harness code, not library source, so it's excluded from `mago`'s checked `[source] paths`.

## Quality tooling

This project uses [Mago](https://mago.carthage.software/) (config in `mago.toml`) for formatting,
linting, and static analysis — there is no PHPStan/PHP_CodeSniffer in this repo.

```bash
mago format --check         # formatting check
mago lint                   # lint rules
mago analyze                # static analysis
```

If a lint rule is a deliberate false positive on a specific line, suppress it with
`// @mago-expect lint:rule-name` immediately above that line rather than disabling the rule globally.

## Continuous integration

- [`continuous-integration.yml`](../../.github/workflows/continuous-integration.yml) runs `mago format
  --check`, `mago lint`, `mago analyze`, and the full PHPUnit suite across PHP 8.4/8.5, against
  lowest/locked/latest dependency sets.
- [`backward-compatibility-check.yml`](../../.github/workflows/backward-compatibility-check.yml) runs
  [`roave/backward-compatibility-check`](https://github.com/Roave/BackwardCompatibilityCheck) against
  the last released tag once one exists, to catch breaking API changes.

## Test conventions

See [copilot-test-generation-instructions.md](../../.github/instructions/copilot-test-generation-instructions.md)
for the full set of conventions (naming, `#[Test]`/`#[CoversClass]` attributes, stub vs. mock usage,
directory layout). In short:

- One test class per source class, mirrored under `test/unit/`.
- Methods use the `#[Test]` attribute with a plain camelCase name describing the scenario — no `test`
  prefix.
- Prefer `$this->createStub(...)` over `$this->createMock(...)` unless you need to assert *how* a
  dependency was called.
- Construct real instances for `final readonly` classes instead of mocking them.

## Backward compatibility

This library is nearing a `1.0.0` release. Once tagged, avoid breaking changes to any class/interface
marked `@api` in its docblock (see [API Reference](./api-reference/index.md)) without a major version
bump — `roave/backward-compatibility-check` enforces this in CI.
