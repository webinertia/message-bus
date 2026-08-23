<?php

declare(strict_types=1);

namespace WebwareTest\MessageBus\Exception;

use App\FooFactory;
use App\FooHandler;
use App\FooMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\Exception\InvalidConfigurationException;

#[CoversClass(InvalidConfigurationException::class)]
final class InvalidConfigurationExceptionTest extends TestCase
{
    #[Test]
    public function forRequiredKeyContainsKeyAndFactory(): void
    {
        $exception = InvalidConfigurationException::forRequiredKey('handlers', FooFactory::class);

        static::assertStringContainsString('handlers', $exception->getMessage());
        static::assertStringContainsString(FooFactory::class, $exception->getMessage());
    }

    #[Test]
    public function fromInvalidHandlerContainsHandlerClassAndActualType(): void
    {
        $exception = InvalidConfigurationException::fromInvalidHandler(
            FooHandler::class,
            'not-a-handler',
            CommandHandlerInterface::class,
        );

        static::assertStringContainsString(FooHandler::class, $exception->getMessage());
        static::assertStringContainsString('string', $exception->getMessage());
        static::assertStringContainsString(CommandHandlerInterface::class, $exception->getMessage());
    }

    #[Test]
    public function fromInvalidTypeContainsKeyAndActualType(): void
    {
        $exception = InvalidConfigurationException::fromInvalidType('handlers', 123);

        static::assertStringContainsString('handlers', $exception->getMessage());
        static::assertStringContainsString('int', $exception->getMessage());
    }

    #[Test]
    public function fromUnMappedMessageContainsMessageClassName(): void
    {
        $exception = InvalidConfigurationException::fromUnMappedMessage(FooMessage::class);

        static::assertStringContainsString(FooMessage::class, $exception->getMessage());
    }
}
