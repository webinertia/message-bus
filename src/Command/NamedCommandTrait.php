<?php

declare(strict_types=1);

namespace Webware\MessageBus\Command;

/**
 * @api
 */
trait NamedCommandTrait
{
    public private(set) ?string $name = null;

    public function getName(): string
    {
        return $this->name ?? static::class;
    }
}
