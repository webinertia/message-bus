<?php

declare(strict_types=1);

namespace Webware\MessageBus\Command;

/**
 * @api
 */
trait NamedCommandTrait
{
    protected readonly string $name;

    public function getName(): string
    {
        return $this->name ?? static::class;
    }
}
