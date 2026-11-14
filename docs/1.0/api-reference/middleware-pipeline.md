# Middleware Pipeline

## `MiddlewareInterface`

The PSR-15-style contract for a single middleware step: `process(MessageInterface $message,
MessageHandlerInterface $handler): ResultInterface`. Implementations decide whether/when to call
`$handler->handle()` and can act on the message or result before/after doing so.

## `MiddlewarePipelineInterface` / `MiddlewarePipe`

`MiddlewarePipelineInterface` extends both `MiddlewareInterface` and `MessageHandlerInterface`, and adds
`pipe(MiddlewareInterface $middleware): void` for registering middleware.

`MiddlewarePipe` is the default implementation, backed by an `SplQueue`. Calling `handle()` runs the
queued middleware against an `EmptyPipelineHandler` as the terminal handler (see below). Cloning a
`MiddlewarePipe` performs a deep clone of its internal queue so pipelines can be reused safely.

## `Next`

Internal, single-use handler that walks the middleware queue one step at a time. Each call to `handle()`
dequeues the next middleware, clones itself for the remaining queue, and invokes the middleware with
that clone as its `$handler`. Once the queue is exhausted it delegates to the configured
`EmptyPipelineHandler`. Calling `handle()` on the same `Next` instance twice throws
`Exception\NextHandlerAlreadyCalledException`.

## `Middleware\MessageHandlerMiddleware`

The library's built-in "terminal" middleware, registered by `ConfigProvider` with the lowest default
priority so it runs last. It uses a `MessageHandlerResolverInterface` to resolve and invoke the handler
mapped to the incoming message, then forwards the resulting `ResultInterface` on to the next handler in
the pipeline so any middleware that runs *after* it (e.g. logging) can inspect the result.

## `Handler\EmptyPipelineHandler`

The default terminal handler used when a `MiddlewarePipe`'s queue is exhausted. If the message it
receives is already a `ResultInterface` (i.e. a result produced by `MessageHandlerMiddleware`), it
returns it as-is; otherwise it throws `Exception\MessageException`, since no handler ever produced a
result.
