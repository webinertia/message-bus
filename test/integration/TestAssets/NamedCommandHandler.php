<?php

declare(strict_types=1);

namespace WebwareTestIntegration\MessageBus\TestAssets;

use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageStatus;

final class NamedCommandHandler implements CommandHandlerInterface
{
    public function namedCommand(NamedCommand $message): CommandResult
    {
        return new CommandResult($message, MessageStatus::Success, 'named-command');
    }
}
