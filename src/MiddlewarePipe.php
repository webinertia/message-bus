<?php

declare(strict_types=1);

namespace Webware\MessageBus;

use Override;
use SplQueue;
use Webware\MessageBus\Exception\NextHandlerAlreadyCalledException;
use Webware\MessageBus\Handler\EmptyPipelineHandler;

final class MiddlewarePipe implements MiddlewarePipelineInterface
{
    /** @var SplQueue<MiddlewareInterface> */
    private SplQueue $pipeline;

    /**
     * Initializes the queue.
     */
    public function __construct()
    {
        $this->pipeline = new SplQueue();
    }

    /**
     * Handle a Command.
     *
     * @throws NextHandlerAlreadyCalledException
     */
    #[Override]
    public function handle(MessageInterface $message): ResultInterface
    {
        return $this->process($message, new EmptyPipelineHandler());
    }

    /**
     * Attach middleware to the pipeline.
     */
    #[Override]
    public function pipe(MiddlewareInterface $middleware): void
    {
        $this->pipeline->enqueue($middleware);
    }

    /**
     * Middleware invocation.
     *
     * Executes the internal pipeline, passing $next as the "final handler".
     * If this looks familiar it's because it works almost exactly like Mezzio.
     * Which is intentional.
     *
     * @throws NextHandlerAlreadyCalledException
     */
    #[Override]
    public function process(MessageInterface $message, PipelineHandlerInterface $next): ResultInterface
    {
        return new Next($this->pipeline, $next)->handle($message);
    }

    /**
     * Perform a deep clone.
     */
    public function __clone(): void
    {
        $this->pipeline = clone $this->pipeline;
    }
}
