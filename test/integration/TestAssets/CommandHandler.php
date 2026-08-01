<?php

declare(strict_types=1);

namespace Webware\MessageBusIntegrationTest\TestAssets;

use Override;
use Webmozart\Assert\Assert;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\ResultInterface;

final class CommandHandler implements CommandHandlerInterface
{
    #[Override]
    public function handle(MessageInterface $message): ResultInterface
    {
        Assert::isInstanceOf(
            $message,
            Command::class,
            'Expected instance of ' . Command::class,
        );

        return new CommandResult($message, MessageStatus::Success, $message->execute());
    }
}
