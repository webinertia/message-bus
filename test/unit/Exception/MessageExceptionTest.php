<?php

declare(strict_types=1);

namespace Webware\MessageBusTest\Exception;

use App\FooMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\Exception\MessageException;

#[CoversClass(MessageException::class)]
final class MessageExceptionTest extends TestCase
{
    #[Test]
    public function createContainsMessageClassName(): void
    {
        $exception = MessageException::create(FooMessage::class);

        static::assertStringContainsString(FooMessage::class, $exception->getMessage());
    }

    #[Test]
    public function fromMessageClassContainsMessageClassName(): void
    {
        $exception = MessageException::fromMessageClass(FooMessage::class);

        static::assertStringContainsString(FooMessage::class, $exception->getMessage());
    }

    #[Test]
    public function messageNotHandledContainsMessageClassName(): void
    {
        $exception = MessageException::messageNotHandled(FooMessage::class);

        static::assertStringContainsString(FooMessage::class, $exception->getMessage());
    }
}
