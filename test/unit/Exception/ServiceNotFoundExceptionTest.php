<?php

declare(strict_types=1);

namespace WebwareTest\MessageBus\Exception;

use App\FooService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use Webware\MessageBus\Exception\ServiceNotFoundException;

#[CoversClass(ServiceNotFoundException::class)]
final class ServiceNotFoundExceptionTest extends TestCase
{
    #[Test]
    public function fromServiceContainsServiceName(): void
    {
        $exception = ServiceNotFoundException::fromService(FooService::class);

        static::assertStringContainsString(FooService::class, $exception->getMessage());
    }

    #[Test]
    public function implementsNotFoundExceptionInterface(): void
    {
        static::assertInstanceOf(NotFoundExceptionInterface::class, ServiceNotFoundException::fromService('x'));
    }
}
