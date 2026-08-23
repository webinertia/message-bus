<?php

declare(strict_types=1);

namespace WebwareTest\MessageBus\TestAssets;

use Override;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

use function array_key_exists;
use function sprintf;

/**
 * Minimal in-memory PSR-11 container test double. Used so resolver tests
 * exercise real container lookups instead of mocking the container's
 * behavior away.
 */
final class InMemoryContainer implements ContainerInterface
{
    /** @var array<string, mixed> */
    private array $entries = [];

    #[Override]
    public function get(string $id): mixed
    {
        if (! $this->has($id)) {
            throw new class(sprintf('Entry not found: %s', $id)) extends RuntimeException implements
                NotFoundExceptionInterface {};
        }

        return $this->entries[$id];
    }

    #[Override]
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->entries);
    }

    public function set(string $id, mixed $value): void
    {
        $this->entries[$id] = $value;
    }
}
