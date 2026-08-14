<?php

declare(strict_types=1);

namespace Webware\MessageBus;

/** @internal */
interface PipelineHandlerInterface
{
    public function handle(MessageInterface $message): ResultInterface;
}
