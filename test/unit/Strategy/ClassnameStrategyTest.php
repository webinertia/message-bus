<?php

declare(strict_types=1);

namespace WebwareTest\MessageBus\Strategy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\Strategy\ClassnameStrategy;
use Webware\MessageBus\StrategyInterface;
use WebwareTest\MessageBus\TestAssets\CreateUser;

#[CoversClass(ClassnameStrategy::class)]
final class ClassnameStrategyTest extends TestCase
{
    #[Test]
    public function handlerMethodInflectsMessageShortClassName(): void
    {
        $strategy = new ClassnameStrategy();

        static::assertSame('createUser', $strategy->handlerMethod(new CreateUser()));
    }

    #[Test]
    public function implementsStrategyInterface(): void
    {
        static::assertInstanceOf(StrategyInterface::class, new ClassnameStrategy());
    }
}
