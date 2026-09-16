<?php

declare(strict_types=1);

namespace Webware\MessageBus\Command;

/**
 * @api
 */
trait NamedCommandTrait
{
    public private(set) string $commandName = self::class;

    public function getCommandName(): string
    {
        return $this->commandName;
    }
}
