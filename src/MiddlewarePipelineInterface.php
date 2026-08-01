<?php

declare(strict_types=1);

namespace Webware\MessageBus;

/** @internal */
interface MiddlewarePipelineInterface extends MiddlewareInterface, MessageHandlerInterface
{
    public function pipe(MiddlewareInterface $middleware): void;
}
