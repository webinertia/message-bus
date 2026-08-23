<?php

declare(strict_types=1);

namespace WebwareTest\MessageBus\Container;

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
use Webware\MessageBus\Container\MessageBusFactory;
use Webware\MessageBus\Exception\ServiceNotFoundException;
use Webware\MessageBus\MessageBus;
use Webware\MessageBus\MiddlewarePipe;
use Webware\MessageBus\MiddlewarePipelineInterface;

#[CoversClass(MessageBusFactory::class)]
final class MessageBusFactoryTest extends TestCase
{
    private MessageBusFactory $factory;

    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    private MiddlewarePipe $middlewarePipeline;

    /**
     * @throws ServiceNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function factoryCanBeInvokedMultipleTimes(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('has')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn(true);

        $container->expects($this->exactly(2))
            ->method('get')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn($this->middlewarePipeline);

        $result1 = ($this->factory)($container);
        $result2 = ($this->factory)($container);

        static::assertInstanceOf(MessageBus::class, $result1);
        static::assertInstanceOf(MessageBus::class, $result2);
        static::assertNotSame($result1, $result2, 'Factory should create new instances each time');
    }

    /**
     * @throws ServiceNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function factoryCreatesNewInstancesWithSameMiddlewarePipeline(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('has')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn(true);

        $container->expects($this->exactly(2))
            ->method('get')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn($this->middlewarePipeline);

        $cmdBus1 = ($this->factory)($container);
        $cmdBus2 = ($this->factory)($container);

        static::assertInstanceOf(MessageBus::class, $cmdBus1);
        static::assertInstanceOf(MessageBus::class, $cmdBus2);
        static::assertNotSame($cmdBus1, $cmdBus2);
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
     * @throws ServiceNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function invokeChecksContainerForCorrectService(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with(static::identicalTo(MiddlewarePipelineInterface::class))
            ->willReturn(true);

        $container->expects($this->once())
            ->method('get')
            ->with(static::identicalTo(MiddlewarePipelineInterface::class))
            ->willReturn($this->middlewarePipeline);

        $result = ($this->factory)($container);

        static::assertInstanceOf(MessageBus::class, $result);
    }

    /**
     * @throws ServiceNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function invokeReturnsMessageBusWhenMiddlewarePipelineIsAvailable(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn(true);

        $container->expects($this->once())
            ->method('get')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn($this->middlewarePipeline);

        $result = ($this->factory)($container);

        static::assertInstanceOf(MessageBus::class, $result);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function invokeThrowsServiceNotFoundExceptionWhenMiddlewarePipelineIsNotAvailable(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with(MiddlewarePipelineInterface::class)
            ->willReturn(false);

        $container->expects($this->never())
            ->method('get');

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage(
            'Service not found: Webware\MessageBus\MiddlewarePipelineInterface was not found in the container',
        );

        ($this->factory)($container);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory   = new MessageBusFactory();
        $this->container = $this->createStub(ContainerInterface::class);

        // Create an actual MiddlewarePipe instance since it's final and cannot be mocked
        $this->middlewarePipeline = new MiddlewarePipe();
    }
}
