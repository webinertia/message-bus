<?php

declare(strict_types=1);

namespace Webware\MessageBus;

/** @internal */
interface MiddlewarePipelineInterface extends MiddlewareInterface, PipelineHandlerInterface
{
    public function pipe(MiddlewareInterface $middleware): void;
}
