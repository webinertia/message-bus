<?php

declare(strict_types=1);

namespace WebwareTest\MessageBus\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\Command\CommandResultInterface;
use Webware\MessageBus\MessageStatus;

#[CoversClass(CommandResult::class)]
final class CommandResultTest extends TestCase
{
    /** @var CommandInterface&Stub */
    private CommandInterface $command;

    private CommandResult $result;

    #[Test]
    public function getCommandReturnsConstructorCommand(): void
    {
        static::assertSame($this->command, $this->result->getCommand());
    }

    #[Test]
    public function getResultReturnsConstructorResult(): void
    {
        static::assertSame('payload', $this->result->getResult());
    }

    #[Test]
    public function getStatusReturnsConstructorStatus(): void
    {
        static::assertSame(MessageStatus::Success, $this->result->getStatus());
    }

    #[Test]
    public function implementsCommandResultInterface(): void
    {
        static::assertInstanceOf(CommandResultInterface::class, $this->result);
    }

    protected function setUp(): void
    {
        $this->command = $this->createStub(CommandInterface::class);
        $this->result  = new CommandResult($this->command, MessageStatus::Success, 'payload');
    }
}
