<?php

declare(strict_types=1);

namespace WebwareTestIntegration\MessageBus\TestAssets;

use Override;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\PipelineHandlerInterface;
use Webware\MessageBus\ResultInterface;

final class TestMiddlewareFirst implements MiddlewareInterface
{
    #[Override]
    public function process(
        MessageInterface $command,
        PipelineHandlerInterface $handler,
    ): ResultInterface {
        // Custom processing logic for this middleware
        return $handler->handle($command);
    }
}
