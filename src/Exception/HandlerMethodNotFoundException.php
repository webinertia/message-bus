<?php

declare(strict_types=1);

namespace Webware\MessageBus\Exception;

use InvalidArgumentException;
use Webware\MessageBus\MessageHandlerInterface;

use function sprintf;

final class HandlerMethodNotFoundException extends InvalidArgumentException
{
    public static function forMethod(
        MessageHandlerInterface $handler,
        string $method,
    ): self {
        return new self(sprintf(
            'Handler "%s" does not have a callable "%s" method.',
            $handler::class,
            $method,
        ));
    }
}
