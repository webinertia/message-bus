<?php

declare(strict_types=1);

namespace Webware\MessageBus\Exception;

use InvalidArgumentException;

use function get_debug_type;
use function sprintf;

final class InvalidConfigurationException extends InvalidArgumentException
{
    public static function forRequiredKey(string $key, string $callingFactory): self
    {
        return new self(sprintf(
            'Missing required configuration key: %s in factory: %s.',
            $key,
            $callingFactory,
        ));
    }

    public static function fromInvalidHandler(string $handlerClass, mixed $handler, string $expectedType): self
    {
        return new self(sprintf(
            'Invalid message handler for "%s". Expected instance of %s, got %s.',
            $handlerClass,
            $expectedType,
            get_debug_type($handler),
        ));
    }

    public static function fromInvalidType(string $key, mixed $value): self
    {
        return new self(sprintf('Invalid type for config key "%s": %s', $key, get_debug_type($value)));
    }

    public static function fromUnMappedMessage(string $messageClass): self
    {
        return new self(sprintf('Missing Map entry for "%s".', $messageClass));
    }
}
