<?php

declare(strict_types=1);

namespace Webware\MessageBus\Container;

use Webware\MessageBus\ConfigProvider;
use Webware\MessageBus\Exception\InvalidConfigurationException;

use function array_key_exists;

/**
 * Builds a validator confirming a pipeline entry defines $key, throwing
 * otherwise so a misconfigured pipeline fails fast instead of erroring later.
 */
function collection_mapper_factory(string $key): callable
{
    $configPath = '$config[' . ConfigProvider::class . '][' . ConfigProvider::MIDDLEWARE_PIPELINE_KEY . ']';

    return /** @throws InvalidConfigurationException */ static function (array $item) use ($key, $configPath): array {
        if (array_key_exists($key, $item)) {
            return $item;
        }

        throw InvalidConfigurationException::fromInvalidType($configPath, $item);
    };
}
