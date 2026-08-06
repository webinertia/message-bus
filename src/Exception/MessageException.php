<?php

declare(strict_types=1);

namespace Webware\MessageBus\Exception;

use RuntimeException;

use function sprintf;

final class MessageException extends RuntimeException
{
    public static function messageNotHandled(string $messageClass): self
    {
        return new self(sprintf('No message handler found for message class "%s".', $messageClass));
    }
}
