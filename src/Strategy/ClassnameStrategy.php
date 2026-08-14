<?php

declare(strict_types=1);

namespace Webware\MessageBus\Strategy;

use Override;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\StrategyInterface;

use function lcfirst;
use function strrpos;
use function substr;

final readonly class ClassnameStrategy implements StrategyInterface
{
    #[Override]
    public function handlerMethod(MessageInterface $message): string
    {
        $fqcn      = $message::class;
        $position  = strrpos($fqcn, needle: '\\');
        $shortName = false === $position ? $fqcn : substr($fqcn, $position + 1);

        return lcfirst($shortName);
    }
}
