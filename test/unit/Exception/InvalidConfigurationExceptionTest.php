<?php

declare(strict_types=1);

namespace Webware\MessageBusTest\Exception;

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
        $exception = InvalidConfigurationException::forRequiredKey('handlers', 'App\\FooFactory');

        static::assertStringContainsString('handlers', $exception->getMessage());
        static::assertStringContainsString('App\\FooFactory', $exception->getMessage());
    }

    #[Test]
    public function fromHandlerNotFoundReturnsServiceNotFoundException(): void
    {
        $exception = InvalidConfigurationException::fromHandlerNotFound('App\\FooHandler');

        static::assertInstanceOf(ServiceNotFoundException::class, $exception);
        static::assertStringContainsString('App\\FooHandler', $exception->getMessage());
    }

    #[Test]
    public function fromInvalidHandlerContainsHandlerClassAndActualType(): void
    {
        $exception = InvalidConfigurationException::fromInvalidHandler('App\\FooHandler', 'not-a-handler');

        static::assertStringContainsString('App\\FooHandler', $exception->getMessage());
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
        $exception = InvalidConfigurationException::fromUnMappedCommand('App\\FooCommand');

        static::assertStringContainsString('App\\FooCommand', $exception->getMessage());
    }

    #[Test]
    public function fromUnMappedMessageContainsMessageClassName(): void
    {
        $exception = InvalidConfigurationException::fromUnMappedMessage('App\\FooMessage');

        static::assertStringContainsString('App\\FooMessage', $exception->getMessage());
    }

    #[Test]
    public function fromUnMappedQueryContainsQueryClassName(): void
    {
        $exception = InvalidConfigurationException::fromUnMappedQuery('App\\FooQuery');

        static::assertStringContainsString('App\\FooQuery', $exception->getMessage());
    }
}
