<?php

declare(strict_types=1);

namespace Webware\MessageBus;

use Override;
use SplQueue;
use Webware\MessageBus\Exception\NextHandlerAlreadyCalledException;
use Webware\MessageBus\Handler\EmptyPipelineHandler;

/** @internal */
final class Next implements MessageHandlerInterface
{
    /** @var SplQueue<MiddlewareInterface> */
    private ?SplQueue $queue;

    /**
     * Clones the queue provided to allow re-use.
     *
     * @param SplQueue<MiddlewareInterface> $queue
     */
    public function __construct(
        SplQueue $queue,
        private MessageHandlerInterface $emptyPipelineHandler = new EmptyPipelineHandler(),
    ) {
        $this->queue = clone $queue;
    }

    #[Override]
    public function handle(MessageInterface $message): ResultInterface
    {
        if (null === $this->queue) {
            throw NextHandlerAlreadyCalledException::create();
        }

        if ($this->queue->isEmpty()) {
            $this->queue = null;

            return $this->emptyPipelineHandler->handle($message);
        }

        $middleware  = $this->queue->dequeue();
        $next        = clone $this;
        $this->queue = null;

        return $middleware->process($message, $next);
    }
}
