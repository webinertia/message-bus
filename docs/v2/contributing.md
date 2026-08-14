# Contributing

## Target Branch

Given a current release of 1.1.0

The next patch release = 1.1.1
The next minor = 1.2.0
The next major = 2.0.0

The branching strategy is as follows (these are the branch names that you will target your PRs to):

* The current release branch will be `1.1.x` (if still under development)
* The next minor branch will be `1.2.x`
* The next major branch will be `2.0.x`

Please target your pull request to the correct branch:

* Documentation improvement: Current release branch
* Bugfix: Current release branch
* QA improvement (unit/integration tests, CS fixes, etc.) that does not change code behavior: Next
  minor `1.2.x`
* New feature/Refactor: Next minor `1.2.x`
* Any Backwards incompatible changes: Next major `2.0.x`

You MUST provide a signoff in your commits for us to accept your contribution/patch. You can do this by
providing either the `--signoff` or `-s` flag when using `git commit`, to certify the
[Developer Certificate of Origin](https://developercertificate.org/).

## Running tests

```bash
composer test             # unit tests only (test/unit)
composer test-integration # integration tests only (test/integration)
composer test-all         # both suites
composer test-coverage    # unit tests + coverage-clover clover.xml
```

## Benchmarks

```bash
composer benchmark # phpbench run --report=aggregate
```

Benchmarks live under `benchmarks/` (config in `phpbench.json.dist`) and measure pipeline dispatch
performance. See [Benchmark Results](./benchmark-results.md) for the latest numbers. This directory is
harness code, not library source, so it's excluded from `mago`'s checked `[source] paths`.

## Quality tooling

This project uses [Mago](https://mago.carthage.software/) (config in `mago.toml`) for formatting,
linting, and static analysis. There is no PHPStan/PHP_CodeSniffer in this repo.

```bash
mago format --check # formatting check
mago lint           # lint rules
mago analyze        # static analysis
mago guard          # structural naming/finality rules
```

`mago guard`'s structural rules (configured under `[[guard.structural.rules]]` in `mago.toml`) enforce
naming conventions across the codebase: interfaces must be suffixed `Interface`, traits `Trait`, and
exception classes in `src/Exception/` must be suffixed `Exception`.

## Continuous integration

* [`continuous-integration.yml`](../../.github/workflows/continuous-integration.yml) runs `mago format
  --check`, `mago lint`, `mago analyze`, `mago guard`, and the full PHPUnit suite across PHP 8.4/8.5,
  against lowest/locked/latest dependency sets. On the locked 8.4 leg it also runs coverage
  (`composer test-coverage`, uploaded to Codecov) and mutation testing (`composer mutation-test`).
* [`backward-compatibility-check.yml`](../../.github/workflows/backward-compatibility-check.yml) runs
  [`roave/backward-compatibility-check`](https://github.com/Roave/BackwardCompatibilityCheck) against
  the last released tag on the same major line, to catch breaking API changes. It skips once a major
  line has no released tag to compare against (for example `2.0.x` before its first release).

## Test conventions

See [copilot-test-generation-instructions.md](../../.github/instructions/copilot-test-generation-instructions.md)
for the full set of conventions (naming, `#[Test]`/`#[CoversClass]` attributes, stub vs. mock usage,
directory layout). In short:

* One test class per source class, mirrored under `test/unit/`.
* Methods use the `#[Test]` attribute with a plain camelCase name describing the scenario. No `test`
  prefix.
* Prefer `$this->createStub(...)` over `$this->createMock(...)` unless you need to assert *how* a
  dependency was called.
* Construct real instances for `final readonly` classes instead of mocking them.

## Backward compatibility

This library is on the `2.0.x` development line. Breaking changes belong in a major version bump, and
`roave/backward-compatibility-check` enforces this in CI. Anything marked `@api` in a class/interface
docblock (see [API Reference](./api-reference/index.md)) is a public contract; avoid breaking it
outside a major release.
