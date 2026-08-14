<?php

declare(strict_types=1);

namespace Webware\MessageBus\Exception;

use InvalidArgumentException;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\QueryHandlerInterface;

use function sprintf;

final class HandlerMethodNotFoundException extends InvalidArgumentException
{
    public static function forMethod(
        CommandHandlerInterface|QueryHandlerInterface $handler,
        string $method,
    ): self {
        return new self(sprintf(
            'Handler "%s" does not have a callable "%s" method.',
            $handler::class,
            $method,
        ));
    }
}
