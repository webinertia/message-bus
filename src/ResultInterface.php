<?php

declare(strict_types=1);

namespace Webware\MessageBus;

/** @internal */
interface ResultInterface extends MessageInterface
{
    public function getResult(): mixed;

    public function getStatus(): StatusInterface;
}
