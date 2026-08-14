<?php

declare(strict_types=1);

namespace Webware\MessageBusBench\Fixtures;

use Override;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\PipelineHandlerInterface;
use Webware\MessageBus\ResultInterface;

/** Stand-in for typical pipeline middleware (logging, auth, etc.) that simply forwards the message on. */
final class PassthroughMiddleware implements MiddlewareInterface
{
    #[Override]
    public function process(
        MessageInterface $message,
        PipelineHandlerInterface $next,
    ): ResultInterface {
        return $next->handle($message);
    }
}
