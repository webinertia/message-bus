<?php

declare(strict_types=1);

namespace Webware\MessageBusIntegrationTest\TestAssets;

use Override;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\PipelineHandlerInterface;
use Webware\MessageBus\ResultInterface;

final class TestMiddlewareSecond implements MiddlewareInterface
{
    #[Override]
    public function process(
        MessageInterface $message,
        PipelineHandlerInterface $handler,
    ): ResultInterface {
        // Custom processing logic for this middleware
        return $handler->handle($message);
    }
}
