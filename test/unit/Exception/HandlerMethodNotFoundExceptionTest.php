<?php

declare(strict_types=1);

namespace Webware\MessageBusTest\Exception;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\Exception\HandlerMethodNotFoundException;

#[CoversClass(HandlerMethodNotFoundException::class)]
final class HandlerMethodNotFoundExceptionTest extends TestCase
{
    #[Test]
    public function forMethodContainsHandlerClassAndMethod(): void
    {
        $handler   = new class() implements CommandHandlerInterface {};
        $exception = HandlerMethodNotFoundException::forMethod($handler, 'createUser');

        static::assertInstanceOf(InvalidArgumentException::class, $exception);
        static::assertStringContainsString($handler::class, $exception->getMessage());
        static::assertStringContainsString('createUser', $exception->getMessage());
    }
}
