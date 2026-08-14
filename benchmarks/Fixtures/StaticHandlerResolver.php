<?php

declare(strict_types=1);

namespace Webware\MessageBusBench\Fixtures;

use Override;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageHandlerResolverInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\QueryHandlerInterface;

/** Always resolves to a single handler; avoids container lookup overhead in the benchmark. */
final readonly class StaticHandlerResolver implements MessageHandlerResolverInterface
{
    public function __construct(
        private CommandHandlerInterface|QueryHandlerInterface $handler,
    ) {}

    #[Override]
    public function resolve(MessageInterface $message): CommandHandlerInterface|QueryHandlerInterface
    {
        return $this->handler;
    }
}
