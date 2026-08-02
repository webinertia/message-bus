<?php

declare(strict_types=1);

namespace Webware\MessageBusIntegrationTest\TestAssets;

use Override;
use Psl\Type;
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
        /** @var Command $message */
        $message = Type\instance_of(Command::class)->assert($message);

        return new CommandResult($message, MessageStatus::Success, $message->execute());
    }
}
