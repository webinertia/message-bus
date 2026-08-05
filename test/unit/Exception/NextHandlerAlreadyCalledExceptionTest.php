<?php

declare(strict_types=1);

namespace Webware\MessageBusTest\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\Exception\NextHandlerAlreadyCalledException;

#[CoversClass(NextHandlerAlreadyCalledException::class)]
final class NextHandlerAlreadyCalledExceptionTest extends TestCase
{
    #[Test]
    public function createReturnsExceptionWithExpectedMessage(): void
    {
        $exception = NextHandlerAlreadyCalledException::create();

        static::assertSame('The next handler has already been called.', $exception->getMessage());
    }
}
