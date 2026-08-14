# Benchmark Results (V2)

Benchmarks are written with [PHPBench](https://phpbench.readthedocs.io/) and live under
[`benchmarks/`](../../benchmarks/). They are not part of `mago`'s checked `[source] paths` — this is
harness/tooling code, not library source, so it isn't held to the same lint/analyze conventions as
`src/`.

## Running the benchmarks

```bash
composer benchmark
```

This runs `phpbench run --report=aggregate` using [`phpbench.json.dist`](../../phpbench.json.dist).

## What's measured

- **`MiddlewarePipeBench`** — dispatches a command through `MessageBus` -> `MiddlewarePipe` with a
  single pass-through middleware ahead of the terminal `MessageHandlerMiddleware`. Handler resolution
  is stubbed with a static resolver wired to the default `HandleStrategy`, so the number isolates the
  cost of pipeline traversal plus the strategy seam (`StrategyInterface::handlerMethod()`, the `is_callable()`
  guard, and dynamic dispatch).
- **`MessageBusDispatchBench`** — the total cost of handling a command end-to-end through a
  container-backed setup (Laminas `ServiceManager`), including real `MessageHandlerResolver`
  container lookups, at 1 and 5 pass-through middleware.

## Latest results

Recorded on 2026-08-13, PHP 8.4.24 (Linux x86_64, WSL2), 1000 revolutions / 5 iterations per subject.

| Benchmark                | Subject     | Set          | Mode (μs) | RStDev  |
|---------------------------|-------------|--------------|-----------|---------|
| `MiddlewarePipeBench`     | benchHandle | 1 middleware | 4.112266  | ±2.04%  |
| `MessageBusDispatchBench` | benchHandle | 1 middleware | 6.089102  | ±8.65%  |
| `MessageBusDispatchBench` | benchHandle | 5 middleware | 7.209593  | ±2.70%  |

V2 adds the strategy seam to `MessageHandlerMiddleware`: resolve the handler, call
`StrategyInterface::handlerMethod()`, guard the name with `is_callable()`, dispatch through the dynamic
method call, and coerce the result to `ResultInterface`. The 1-middleware dispatch figure carries a
wide ±8.65% RStDev, so treat that single delta as indicative rather than precise.

Re-run `composer benchmark` and update this table after any change likely to affect pipeline or
resolver performance.
