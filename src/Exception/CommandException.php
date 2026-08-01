<?php

declare(strict_types=1);

namespace Webware\MessageBus\Exception;

use RuntimeException;

use function sprintf;

final class CommandException extends RuntimeException
{
    public static function commandNotHandled(string $commandClass): self
    {
        return new self(sprintf('No command handler found for command class "%s".', $commandClass));
    }

    public static function create(string $commandClass): self
    {
        return new self(
            sprintf(
                'No command handler found for command class "%s".',
                $commandClass,
            ),
        );
    }

    public static function fromCommandClass(string $commandClass): self
    {
        return new self(sprintf('No command handler found for command class "%s".', $commandClass));
    }
}
