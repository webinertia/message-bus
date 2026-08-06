<?php

declare(strict_types=1);

namespace Webware\MessageBus\Command;

use Override;
use Webware\MessageBus\ResultInterface;
use Webware\MessageBus\StatusInterface;

/**
 * @internal
 */
interface CommandResultInterface extends CommandInterface, ResultInterface
{
    public function getCommand(): CommandInterface;

    #[Override]
    public function getResult(): mixed;

    #[Override]
    public function getStatus(): StatusInterface;
}
