<?php

declare(strict_types=1);

namespace Webware\MessageBus\Command;

use Override;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\StatusInterface;

final readonly class CommandResult implements CommandResultInterface
{
    public function __construct(
        private CommandInterface $command,
        private MessageStatus $status,
        private mixed $result,
    ) {}

    #[Override]
    public function getCommand(): CommandInterface
    {
        return $this->command;
    }

    #[Override]
    public function getResult(): mixed
    {
        return $this->result;
    }

    #[Override]
    public function getStatus(): StatusInterface
    {
        return $this->status;
    }
}
