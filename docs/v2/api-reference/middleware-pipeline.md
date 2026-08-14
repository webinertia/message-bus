# Middleware Pipeline

## `MiddlewareInterface`

The PSR-15-style contract for a single middleware step:

```php
public function process(
    MessageInterface $message,
    PipelineHandlerInterface $next,
): ResultInterface;
```

Implementations decide whether/when to call `$next->handle()` and can act on the message or result
before/after doing so.

## `MiddlewarePipelineInterface` / `MiddlewarePipe`

`MiddlewarePipelineInterface` extends both `MiddlewareInterface` and `PipelineHandlerInterface`, and
adds `pipe(MiddlewareInterface $middleware): void` for registering middleware.

`MiddlewarePipe` is the default implementation, backed by an `SplQueue`. Calling `handle()` runs the
queued middleware against an `EmptyPipelineHandler` as the terminal handler (see below). Cloning a
`MiddlewarePipe` performs a deep clone of its internal queue so pipelines can be reused safely.

## `Next`

Internal, single-use handler that walks the middleware queue one step at a time. Each call to `handle()`
dequeues the next middleware, clones itself for the remaining queue, and invokes the middleware with
that clone as its `$next`. Once the queue is exhausted it delegates to the configured
`EmptyPipelineHandler`. Calling `handle()` on the same `Next` instance twice throws
`Exception\NextHandlerAlreadyCalledException`.

## `Middleware\MessageHandlerMiddleware`

The library's built-in terminal middleware, registered by `ConfigProvider` with the lowest default
priority so it runs last. Its constructor takes a `MessageHandlerResolverInterface` and a
`StrategyInterface`.

`process()` resolves the handler, asks the strategy for the method name, and guards it with
`is_callable()`. A handler without that method throws
`Exception\HandlerMethodNotFoundException`. It then invokes the method and forwards the returned
`ResultInterface` to `$next->handle()`, so middleware registered after it can inspect the result. A
handler method that returns a non-`ResultInterface` value raises a `TypeError`.

## `Handler\EmptyPipelineHandler`

The default terminal handler used when a `MiddlewarePipe`'s queue is exhausted. If the message it
receives is already a `ResultInterface` (a result produced by `MessageHandlerMiddleware`), it returns
it as-is; otherwise it throws `Exception\MessageException`, since no handler ever produced a result.
