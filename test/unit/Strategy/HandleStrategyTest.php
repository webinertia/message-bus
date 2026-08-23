<?php

declare(strict_types=1);

namespace WebwareTest\MessageBus\Strategy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\Strategy\HandleStrategy;
use Webware\MessageBus\StrategyInterface;

#[CoversClass(HandleStrategy::class)]
final class HandleStrategyTest extends TestCase
{
    #[Test]
    public function handlerMethodReturnsHandle(): void
    {
        $message  = $this->createStub(MessageInterface::class);
        $strategy = new HandleStrategy();

        static::assertSame('handle', $strategy->handlerMethod($message));
    }

    #[Test]
    public function implementsStrategyInterface(): void
    {
        static::assertInstanceOf(StrategyInterface::class, new HandleStrategy());
    }
}
