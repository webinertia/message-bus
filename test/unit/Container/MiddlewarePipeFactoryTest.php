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
use Webware\MessageBus\ConfigProvider;
use Webware\MessageBus\Container\MiddlewarePipeFactory;
use Webware\MessageBus\Exception\InvalidConfigurationException;
use Webware\MessageBus\Exception\ServiceNotFoundException;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\MiddlewarePipe;
use Webware\MessageBus\MiddlewarePipelineInterface;
use Webware\MessageBus\ResultInterface;

/**
 * @mago-expect lint:kan-defect
 */
#[CoversClass(MiddlewarePipeFactory::class)]
final class MiddlewarePipeFactoryTest extends TestCase
{
    private MiddlewarePipeFactory $factory;

    /** @var ContainerInterface&MockObject */
    private ContainerInterface $container;

    /** @var MiddlewareInterface&MockObject */
    private MiddlewareInterface $middleware1;

    /** @var MiddlewareInterface&MockObject */
    private MiddlewareInterface $middleware2;

    /**
     * @throws ServiceNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function factoryCanBeInvokedMultipleTimes(): void
    {
        $config = [
            MessageBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->exactly(2))
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $container->expects($this->exactly(2))
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $result1 = ($this->factory)($container);
        $result2 = ($this->factory)($container);

        static::assertInstanceOf(MiddlewarePipelineInterface::class, $result1);
        static::assertInstanceOf(MiddlewarePipelineInterface::class, $result2);
        static::assertNotSame($result1, $result2, 'Factory should create new instances each time');
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
    public function invokeContinuesPastUnavailableMiddlewareToPipeSubsequentOnes(): void
    {
        $config = [
            MessageBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [
                    [
                        // Higher priority, dequeued first, but unavailable.
                        'middleware' => 'TestMiddleware1',
                        'priority'   => 10,
                    ],
                    [
                        // Lower priority, dequeued second, and available.
                        'middleware' => 'TestMiddleware2',
                        'priority'   => 5,
                    ],
                ],
            ],
        ];

        $expectedResult = $this->createStub(ResultInterface::class);

        /** @var MiddlewareInterface&MockObject $middleware2 */
        $middleware2 = $this->createMock(MiddlewareInterface::class);
        $middleware2->expects($this->once())
            ->method('process')
            ->willReturn($expectedResult);

        $this->container->method('has')
            ->willReturnCallback(static fn($service) => match ($service) {
                'config', 'TestMiddleware2' => true,
                default                     => false,
            });

        $this->container->method('get')
            ->willReturnCallback(static fn($service) => match ($service) {
                'config'          => $config,
                'TestMiddleware2' => $middleware2,
                default           => null,
            });

        $result = ($this->factory)($this->container);

        // A Continue_ -> break mutant would abort the loop at the first
        // unavailable entry, so TestMiddleware2 would never get piped in.
        static::assertSame($expectedResult, $result->handle($this->createStub(MessageInterface::class)));
    }

    /**
     * @throws ServiceNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function invokePipesResolvedMiddlewareIntoTheResultingPipeline(): void
    {
        $config = [
            MessageBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [
                    [
                        'middleware' => 'TestMiddleware1',
                        'priority'   => 10,
                    ],
                ],
            ],
        ];

        $expectedResult = $this->createStub(ResultInterface::class);

        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->willReturn($expectedResult);

        $this->container->method('has')
            ->willReturnCallback(static fn($service) => match ($service) {
                'config', 'TestMiddleware1' => true,
                default                     => false,
            });

        $this->container->method('get')
            ->willReturnCallback(static fn($service) => match ($service) {
                'config'          => $config,
                'TestMiddleware1' => $middleware,
                default           => null,
            });

        $result = ($this->factory)($this->container);

        // If the middleware was never piped in, handling would fall through to
        // EmptyPipelineHandler instead of invoking our mock's process() method.
        static::assertSame($expectedResult, $result->handle($this->createStub(MessageInterface::class)));
    }

    /**
     * @throws ServiceNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function invokeReturnsMiddlewarePipelineInterface(): void
    {
        $config = [
            MessageBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $result = ($this->factory)($container);

        static::assertInstanceOf(MiddlewarePipelineInterface::class, $result);
        static::assertInstanceOf(MiddlewarePipe::class, $result);
    }

    /**
     * @throws ServiceNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function invokeSkipsMiddlewareNotAvailableInContainer(): void
    {
        $config = [
            MessageBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [
                    [
                        'middleware' => 'TestMiddleware1',
                        'priority'   => 10,
                    ],
                    [
                        'middleware' => 'TestMiddleware2',
                        'priority'   => 5,
                    ],
                ],
            ],
        ];

        $this->container->method('has')
            ->willReturnCallback(static fn($service) => match ($service) {
                'config', 'TestMiddleware1' => true,
                'TestMiddleware2'           => false,
                default                     => false,
            });

        $this->container->method('get')
            ->willReturnCallback(fn($service) => match ($service) {
                'config'          => $config,
                'TestMiddleware1' => $this->middleware1,
                default           => null,
            });

        $result = ($this->factory)($this->container);

        static::assertInstanceOf(MiddlewarePipelineInterface::class, $result);
    }

    /**
     * @throws ServiceNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function invokeThrowsExceptionWhenConfigServiceNotFound(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(false);

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('Service not found: config was not found in the container');

        ($this->factory)($container);
    }

    /**
     * @throws ServiceNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function invokeThrowsExceptionWhenMessageBusInterfaceKeyMissing(): void
    {
        $config = [];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Missing required configuration key: Webware\MessageBus\MessageBusInterface in factory: Webware\MessageBus\Container\MiddlewarePipeFactory.', // phpcs:ignore
        );

        ($this->factory)($container);
    }

    /**
     * @throws ServiceNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function invokeThrowsExceptionWhenMiddlewareConfigMissingMiddlewareKey(): void
    {
        $config = [
            MessageBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [
                    [
                        'priority' => 10,
                        // Missing 'middleware' key
                    ],
                ],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'Invalid type for config key "$config[Webware\MessageBus\ConfigProvider][middleware_pipeline]": array',
        );

        ($this->factory)($container);
    }

    /**
     * @throws ServiceNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function invokeWithEmptyMiddlewarePipelineConfig(): void
    {
        $config = [
            MessageBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [],
            ],
        ];

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);

        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $result = ($this->factory)($container);

        static::assertInstanceOf(MiddlewarePipelineInterface::class, $result);
    }

    /**
     * @throws ServiceNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function invokeWithMiddlewareDefaultPriority(): void
    {
        $config = [
            MessageBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [
                    [
                        'middleware' => 'TestMiddleware1',
                        // No priority specified, should default to 1
                    ],
                ],
            ],
        ];

        $this->container->method('has')
            ->willReturnCallback(static fn($service) => match ($service) {
                'config', 'TestMiddleware1' => true,
                default                     => false,
            });

        $this->container->method('get')
            ->willReturnCallback(fn($service) => match ($service) {
                'config'          => $config,
                'TestMiddleware1' => $this->middleware1,
                default           => null,
            });

        $result = ($this->factory)($this->container);

        static::assertInstanceOf(MiddlewarePipelineInterface::class, $result);
    }

    /**
     * @throws ServiceNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function invokeWithMiddlewarePipelineConfiguration(): void
    {
        $config = [
            MessageBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [
                    [
                        'middleware' => 'TestMiddleware1',
                        'priority'   => 10,
                    ],
                    [
                        'middleware' => 'TestMiddleware2',
                        'priority'   => 5,
                    ],
                ],
            ],
        ];

        $this->container->method('has')
            ->willReturnCallback(static fn($service) => match ($service) {
                'config', 'TestMiddleware1', 'TestMiddleware2' => true,
                default                                        => false,
            });

        $this->container->method('get')
            ->willReturnCallback(fn($service) => match ($service) {
                'config'          => $config,
                'TestMiddleware1' => $this->middleware1,
                'TestMiddleware2' => $this->middleware2,
                default           => null,
            });

        $result = ($this->factory)($this->container);

        static::assertInstanceOf(MiddlewarePipelineInterface::class, $result);
    }

    /**
     * @throws ServiceNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[Test]
    public function invokeWithNonIntegerPriorityDefaultsToOne(): void
    {
        $config = [
            MessageBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [
                    [
                        'middleware' => 'TestMiddleware1',
                        'priority'   => 'invalid', // Non-integer priority
                    ],
                ],
            ],
        ];

        $this->container->method('has')
            ->willReturnCallback(static fn($service) => match ($service) {
                'config', 'TestMiddleware1' => true,
                default                     => false,
            });

        $this->container->method('get')
            ->willReturnCallback(fn($service) => match ($service) {
                'config'          => $config,
                'TestMiddleware1' => $this->middleware1,
                default           => null,
            });

        $result = ($this->factory)($this->container);

        static::assertInstanceOf(MiddlewarePipelineInterface::class, $result);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->factory     = new MiddlewarePipeFactory();
        $this->container   = $this->createStub(ContainerInterface::class);
        $this->middleware1 = $this->createStub(MiddlewareInterface::class);
        $this->middleware2 = $this->createStub(MiddlewareInterface::class);
    }
}
