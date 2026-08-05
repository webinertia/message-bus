<?php

declare(strict_types=1);

namespace Webware\MessageBusTest\Exception;

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
        $exception = MessageException::create('App\\FooMessage');

        static::assertStringContainsString('App\\FooMessage', $exception->getMessage());
    }

    #[Test]
    public function fromMessageClassContainsMessageClassName(): void
    {
        $exception = MessageException::fromMessageClass('App\\FooMessage');

        static::assertStringContainsString('App\\FooMessage', $exception->getMessage());
    }

    #[Test]
    public function messageNotHandledContainsMessageClassName(): void
    {
        $exception = MessageException::messageNotHandled('App\\FooMessage');

        static::assertStringContainsString('App\\FooMessage', $exception->getMessage());
    }
}
