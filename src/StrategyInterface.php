<?php

declare(strict_types=1);

namespace Webware\MessageBus;

/** @api */
interface StrategyInterface
{
    public function match(MessageInterface $message): string;
}
