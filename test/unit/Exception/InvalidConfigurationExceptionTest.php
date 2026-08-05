<?php

declare(strict_types=1);

namespace Webware\MessageBusTest\Exception;

use App\FooCommand;
use App\FooFactory;
use App\FooHandler;
use App\FooMessage;
use App\FooQuery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\Exception\InvalidConfigurationException;
use Webware\MessageBus\Exception\ServiceNotFoundException;

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
    public function fromHandlerNotFoundReturnsServiceNotFoundException(): void
    {
        $exception = InvalidConfigurationException::fromHandlerNotFound(FooHandler::class);

        static::assertInstanceOf(ServiceNotFoundException::class, $exception);
        static::assertStringContainsString(FooHandler::class, $exception->getMessage());
    }

    #[Test]
    public function fromInvalidHandlerContainsHandlerClassAndActualType(): void
    {
        $exception = InvalidConfigurationException::fromInvalidHandler(FooHandler::class, 'not-a-handler');

        static::assertStringContainsString(FooHandler::class, $exception->getMessage());
        static::assertStringContainsString('string', $exception->getMessage());
    }

    #[Test]
    public function fromInvalidTypeContainsKeyAndActualType(): void
    {
        $exception = InvalidConfigurationException::fromInvalidType('handlers', 123);

        static::assertStringContainsString('handlers', $exception->getMessage());
        static::assertStringContainsString('int', $exception->getMessage());
    }

    #[Test]
    public function fromInvalidValueContainsKeyAndActualType(): void
    {
        $exception = InvalidConfigurationException::fromInvalidValue('handlers', 123);

        static::assertStringContainsString('handlers', $exception->getMessage());
        static::assertStringContainsString('int', $exception->getMessage());
    }

    #[Test]
    public function fromMissingKeyContainsKey(): void
    {
        $exception = InvalidConfigurationException::fromMissingKey('handlers');

        static::assertStringContainsString('handlers', $exception->getMessage());
    }

    #[Test]
    public function fromUnMappedCommandContainsCommandClassName(): void
    {
        $exception = InvalidConfigurationException::fromUnMappedCommand(FooCommand::class);

        static::assertStringContainsString(FooCommand::class, $exception->getMessage());
    }

    #[Test]
    public function fromUnMappedMessageContainsMessageClassName(): void
    {
        $exception = InvalidConfigurationException::fromUnMappedMessage(FooMessage::class);

        static::assertStringContainsString(FooMessage::class, $exception->getMessage());
    }

    #[Test]
    public function fromUnMappedQueryContainsQueryClassName(): void
    {
        $exception = InvalidConfigurationException::fromUnMappedQuery(FooQuery::class);

        static::assertStringContainsString(FooQuery::class, $exception->getMessage());
    }
}
