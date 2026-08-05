<?php

declare(strict_types=1);

namespace Webware\MessageBusTest\Exception;

use App\FooCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\Exception\CommandException;

#[CoversClass(CommandException::class)]
final class CommandExceptionTest extends TestCase
{
    #[Test]
    public function commandNotHandledContainsCommandClassName(): void
    {
        $exception = CommandException::commandNotHandled(FooCommand::class);

        static::assertStringContainsString(FooCommand::class, $exception->getMessage());
    }

    #[Test]
    public function createContainsCommandClassName(): void
    {
        $exception = CommandException::create(FooCommand::class);

        static::assertStringContainsString(FooCommand::class, $exception->getMessage());
    }

    #[Test]
    public function fromCommandClassContainsCommandClassName(): void
    {
        $exception = CommandException::fromCommandClass(FooCommand::class);

        static::assertStringContainsString(FooCommand::class, $exception->getMessage());
    }
}
