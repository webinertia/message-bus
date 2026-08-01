<?php

declare(strict_types=1);

namespace Webware\MessageBus\Command;

/**
 * @api
 */
interface NamedCommandInterface extends CommandInterface
{
    public function getName(): string;
}
