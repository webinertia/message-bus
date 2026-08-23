<?php

declare(strict_types=1);

namespace WebwareTest\MessageBus\Container;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use stdClass;
use TypeError;
use Webware\MessageBus\Container\MessageHandlerMiddlewareFactory;
use Webware\MessageBus\MessageHandlerResolver;
use Webware\MessageBus\MessageHandlerResolverInterface;
use Webware\MessageBus\Middleware\MessageHandlerMiddleware;
use Webware\MessageBus\StrategyInterface;

#[CoversClass(MessageHandlerMiddlewareFactory::class)]
final class MessageHandlerMiddlewareFactoryTest extends TestCase
{
    private MessageHandlerMiddlewareFactory $factory;

    private MessageHandlerResolver $resolver;

    /** @var StrategyInterface&Stub */
    private StrategyInterface $strategy;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function factoryCanBeInvokedMultipleTimes(): void
    {
        $container = $this->createContainerStub();

        $result1 = ($this->factory)($container);
        $result2 = ($this->factory)($container);

        static::assertInstanceOf(MessageHandlerMiddleware::class, $result1);
        static::assertInstanceOf(MessageHandlerMiddleware::class, $result2);
        static::assertNotSame($result1, $result2);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function factoryInjectsResolverAndStrategyIntoMiddleware(): void
    {
        $container = $this->createContainerStub();

        $middleware = ($this->factory)($container);

        $resolverProperty = new ReflectionClass($middleware)->getProperty('resolver');
        $strategyProperty = new ReflectionClass($middleware)->getProperty('strategy');

        static::assertSame($this->resolver, $resolverProperty->getValue($middleware));
        static::assertSame($this->strategy, $strategyProperty->getValue($middleware));
    }

    #[Test]
    public function factoryIsCallable(): void
    {
        static::assertSame(
            '__invoke',
            new ReflectionClass($this->factory)->getMethod('__invoke')
                ->getName(),
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function invokeThrowsTypeErrorWhenResolverIsWrongType(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(
                static fn(string $id): mixed => match ($id) {
                    MessageHandlerResolverInterface::class => new stdClass(),
                    default                                => null,
                },
            );

        $this->expectException(TypeError::class);

        ($this->factory)($container);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new MessageHandlerMiddlewareFactory();

        $resolverContainer = $this->createStub(ContainerInterface::class);
        $this->resolver    = new MessageHandlerResolver($resolverContainer);
        $this->strategy    = $this->createStub(StrategyInterface::class);
    }

    private function createContainerStub(): ContainerInterface
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')
            ->willReturnCallback(
                fn(string $id): mixed => match ($id) {
                    MessageHandlerResolverInterface::class => $this->resolver,
                    StrategyInterface::class               => $this->strategy,
                    default                                => null,
                },
            );

        return $container;
    }
}
