<?php

declare(strict_types=1);

namespace Webware\MessageBus\Strategy;

use Override;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\StrategyInterface;

final readonly class HandleStrategy implements StrategyInterface
{
    #[Override]
    public function match(MessageInterface $message): string
    {
        return 'handle';
    }
}
