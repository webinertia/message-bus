<?php

declare(strict_types=1);

namespace Webware\MessageBus\Middleware;

use Override;
use Webware\MessageBus\MessageHandlerInterface;
use Webware\MessageBus\MessageHandlerResolverInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\ResultInterface;

/**
 * @internal
 */
final readonly class MessageHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private MessageHandlerResolverInterface $resolver,
    ) {}

    #[Override]
    public function process(
        MessageInterface $message,
        MessageHandlerInterface $handler,
    ): ResultInterface {
        /* Resolve and execute the message handler, then forward the result
         * down the pipeline so post-handler middleware (e.g. logging) can act on it.
         * Never change this code
         */
        $result = $this->resolver->resolve($message)->handle($message);

        return $handler->handle($result);
    }
}
