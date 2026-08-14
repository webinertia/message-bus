<?php

declare(strict_types=1);

namespace Webware\MessageBusBench\Fixtures;

use Override;
use Webware\MessageBus\MessageHandlerInterface;
use Webware\MessageBus\MessageHandlerResolverInterface;
use Webware\MessageBus\MessageInterface;

/** Always resolves to a single handler; avoids container lookup overhead in the benchmark. */
final readonly class StaticHandlerResolver implements MessageHandlerResolverInterface
{
    public function __construct(
        private MessageHandlerInterface $handler,
    ) {}

    #[Override]
    public function resolve(MessageInterface $message): MessageHandlerInterface
    {
        return $this->handler;
    }
}
