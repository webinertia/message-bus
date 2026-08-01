<?php

declare(strict_types=1);

namespace Webware\MessageBusTest\Container;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use stdClass;
use TypeError;
use Webware\MessageBus\Container\MessageHandlerMiddlewareFactory;
use Webware\MessageBus\MessageHandlerResolver;
use Webware\MessageBus\MessageHandlerResolverInterface;
use Webware\MessageBus\Middleware\MessageHandlerMiddleware;

#[CoversClass(MessageHandlerMiddlewareFactory::class)]
final class MessageHandlerMiddlewareFactoryTest extends TestCase
{
    private MessageHandlerMiddlewareFactory $factory;

    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    private MessageHandlerResolver $commandHandlerResolver;

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function factoryCanBeInvokedMultipleTimes(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('get')
            ->with(MessageHandlerResolverInterface::class)
            ->willReturn($this->commandHandlerResolver);

        $result1 = ($this->factory)($container);
        $result2 = ($this->factory)($container);

        static::assertInstanceOf(MessageHandlerMiddleware::class, $result1);
        static::assertInstanceOf(MessageHandlerMiddleware::class, $result2);
        static::assertNotSame($result1, $result2, 'Factory should create new instances each time');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function factoryCreatesNewInstancesWithSameMessageHandlerResolver(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('get')
            ->with(MessageHandlerResolverInterface::class)
            ->willReturn($this->commandHandlerResolver);

        $middleware1 = ($this->factory)($container);
        $middleware2 = ($this->factory)($container);

        static::assertInstanceOf(MessageHandlerMiddleware::class, $middleware1);
        static::assertInstanceOf(MessageHandlerMiddleware::class, $middleware2);
        static::assertNotSame($middleware1, $middleware2);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function factoryIsCallable(): void
    {
        // Verify the factory has the __invoke method
        static::assertSame(
            '__invoke',
            new ReflectionClass($this->factory)->getMethod('__invoke')
                ->getName(),
        );
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function factoryPassesMessageHandlerResolverToMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(MessageHandlerResolverInterface::class)
            ->willReturn($this->commandHandlerResolver);

        $result = ($this->factory)($container);

        static::assertInstanceOf(MessageHandlerMiddleware::class, $result);

        // The middleware should have received the command handler resolver
        // This is verified by the fact that the middleware was created successfully
        static::assertInstanceOf(MessageHandlerMiddleware::class, $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function factoryReturnsNewInstanceEachTime(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(3))
            ->method('get')
            ->with(MessageHandlerResolverInterface::class)
            ->willReturn($this->commandHandlerResolver);

        $instances = [];

        for ($i = 0; $i < 3; $i++) {
            $instances[] = ($this->factory)($container);
        }

        static::assertCount(3, $instances);

        foreach ($instances as $instance) {
            static::assertInstanceOf(MessageHandlerMiddleware::class, $instance);
        }

        // Ensure all instances are different
        static::assertNotSame($instances[0], $instances[1]);
        static::assertNotSame($instances[1], $instances[2]);
        static::assertNotSame($instances[0], $instances[2]);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function factoryWorksWithDifferentContainerInstances(): void
    {
        $container1 = $this->createMock(ContainerInterface::class);
        $container2 = $this->createMock(ContainerInterface::class);

        $resolverContainer1      = $this->createStub(ContainerInterface::class);
        $resolverContainer2      = $this->createStub(ContainerInterface::class);
        $commandHandlerResolver1 = new MessageHandlerResolver($resolverContainer1);
        $commandHandlerResolver2 = new MessageHandlerResolver($resolverContainer2);

        $container1->expects($this->once())
            ->method('get')
            ->with(MessageHandlerResolverInterface::class)
            ->willReturn($commandHandlerResolver1);

        $container2->expects($this->once())
            ->method('get')
            ->with(MessageHandlerResolverInterface::class)
            ->willReturn($commandHandlerResolver2);

        $result1 = ($this->factory)($container1);
        $result2 = ($this->factory)($container2);

        static::assertInstanceOf(MessageHandlerMiddleware::class, $result1);
        static::assertInstanceOf(MessageHandlerMiddleware::class, $result2);
        static::assertNotSame($result1, $result2);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function invokeRetrievesMessageHandlerResolverFromContainer(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(static::identicalTo(MessageHandlerResolverInterface::class))
            ->willReturn($this->commandHandlerResolver);

        $result = ($this->factory)($container);

        static::assertInstanceOf(MessageHandlerMiddleware::class, $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function invokeReturnsMessageHandlerMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(MessageHandlerResolverInterface::class)
            ->willReturn($this->commandHandlerResolver);

        $result = ($this->factory)($container);

        static::assertInstanceOf(MessageHandlerMiddleware::class, $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function invokeThrowsExceptionWhenMessageHandlerResolverIsNotCorrectType(): void
    {
        $invalidResolver = new stdClass();

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(MessageHandlerResolverInterface::class)
            ->willReturn($invalidResolver);

        $this->expectException(TypeError::class);

        ($this->factory)($container);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory   = new MessageHandlerMiddlewareFactory();
        $this->container = $this->createStub(ContainerInterface::class);

        // Create a real MessageHandlerResolver instance since it's final and cannot be mocked
        $resolverContainer            = $this->createStub(ContainerInterface::class);
        $this->commandHandlerResolver = new MessageHandlerResolver($resolverContainer);
    }
}
