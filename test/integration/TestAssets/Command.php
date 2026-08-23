<?php

declare(strict_types=1);

namespace WebwareTestIntegration\MessageBus\TestAssets;

use Webware\MessageBus\Command\CommandInterface;

final class Command implements CommandInterface
{
    public function __construct(
        public string $name = 'Command-One',
    ) {}

    public function execute(): mixed
    {
        return $this->name;
    }
}
