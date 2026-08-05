<?php

declare(strict_types=1);

namespace Webware\MessageBusBench\Fixtures;

use Webware\MessageBus\Command\CommandInterface;

final class BenchCommand implements CommandInterface
{
    public function execute(): mixed
    {
        return 'benchmark-result';
    }
}
