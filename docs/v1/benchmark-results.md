# Benchmark Results

Benchmarks are written with [PHPBench](https://phpbench.readthedocs.io/) and live under
[`benchmarks/`](../../benchmarks/). They are not part of `mago`'s checked `[source] paths`, this is
harness/tooling code, not library source, so it isn't held to the same lint/analyze conventions as
`src/`.

## Running the benchmarks

```bash
composer benchmark
```

This runs `phpbench run --report=aggregate` using [`phpbench.json.dist`](../../phpbench.json.dist).

## What's measured

- **`MiddlewarePipeBench`**: dispatches a command through `MessageBus` -> `MiddlewarePipe` with a
  single pass-through middleware ahead of the terminal `MessageHandlerMiddleware`. Handler resolution
  is stubbed with a static resolver so the number isolates the cost of pipeline traversal itself
  (queue cloning/dequeue per `Next` invocation).
- **`MessageBusDispatchBench`**: the total cost of handling a command end-to-end through a
  container-backed setup (Laminas `ServiceManager`), including real `MessageHandlerResolver`
  container lookups, matching how the bus is actually wired in an application, at 1 and 5
  pass-through middleware.

## Latest results

Recorded on 2026-08-06, PHP 8.4.24 (Linux x86_64, WSL2), 1000 revolutions / 5 iterations per subject.

| Benchmark                | Subject     | Set          | Mode (μs) | RStDev  |
|---------------------------|-------------|--------------|-----------|---------|
| `MiddlewarePipeBench`     | benchHandle | 1 middleware | 3.363319  | ±3.81%  |
| `MessageBusDispatchBench` | benchHandle | 1 middleware | 4.650117  | ±1.62%  |
| `MessageBusDispatchBench` | benchHandle | 5 middleware | 6.909352  | ±1.93%  |

`MessageBusDispatchBench` rose by roughly 0.35-0.56μs from the previous baseline (4.300098μs /
6.345840μs) after `MessageHandlerResolver::resolve()` gained a command/query handler-type check
(rejecting a resolved handler that doesn't implement the expected `CommandHandlerInterface`/
`QueryHandlerInterface`). `MiddlewarePipeBench`, which never touches the resolver, stayed flat,
confirming the increase is attributable to that added validation rather than noise.

At 1 middleware, `MessageBusDispatchBench` (real PSR-11 container + config-driven wiring) costs
about 1.3μs more than the equivalent `MiddlewarePipeBench` case, that's the overhead
`MessageHandlerResolver`'s container lookup and handler-type check add on top of pure pipeline
traversal. Going from 1 to 5 middleware in `MessageBusDispatchBench` adds roughly 0.6μs per
middleware, consistent with `MiddlewarePipe` cloning the internal `SplQueue` once per
`Next::handle()` call.

Re-run `composer benchmark` and update this table after any change likely to affect pipeline or
resolver performance.
