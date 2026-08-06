<?php

declare(strict_types=1);

namespace Webware\MessageBus;

use Override;

final readonly class MessageBus implements MessageBusInterface
{
    public function __construct(
        private MiddlewarePipelineInterface $pipeline,
    ) {}

    #[Override]
    public function handle(MessageInterface $message): ResultInterface
    {
        return $this->pipeline->handle($message);
    }
}
