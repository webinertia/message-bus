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
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\ConfigProvider;
use Webware\MessageBus\Container\MiddlewarePipeFactory;
use Webware\MessageBus\Exception\InvalidConfigurationException;
use Webware\MessageBus\Exception\ServiceNotFoundException;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageHandlerResolver;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Middleware\MessageHandlerMiddleware;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\MiddlewarePipe;
use Webware\MessageBus\MiddlewarePipelineInterface;
use Webware\MessageBus\PipelineHandlerInterface;
use Webware\MessageBus\ResultInterface;
use Webware\MessageBus\Strategy\HandleStrategy;

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
    public function invokePipesMiddlewareInPriorityOrder(): void
    {
        $config = [
            MessageBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [
                    // Declared first, but lower priority: must run second.
                    ['middleware' => 'LowPriority', 'priority' => 1],
                    // Declared second, but higher priority: must run first.
                    ['middleware' => 'HighPriority', 'priority' => 10],
                ],
            ],
        ];

        $executionOrder = [];

        $lowPriority = new class($executionOrder) implements MiddlewareInterface {
            /** @param array<string> $executionOrder */
            public function __construct(
                private array &$executionOrder,
            ) {}

            #[Override]
            public function process(MessageInterface $message, PipelineHandlerInterface $next): ResultInterface
            {
                $this->executionOrder[] = 'low';

                return $next->handle($message);
            }
        };

        $highPriority = new class($executionOrder) implements MiddlewareInterface {
            /** @param array<string> $executionOrder */
            public function __construct(
                private array &$executionOrder,
            ) {}

            #[Override]
            public function process(MessageInterface $message, PipelineHandlerInterface $next): ResultInterface
            {
                $this->executionOrder[] = 'high';

                return $next->handle($message);
            }
        };

        $this->container->method('has')
            ->willReturnCallback(static fn($service) => match ($service) {
                'config', 'LowPriority', 'HighPriority' => true,
                default                                 => false,
            });

        $this->container->method('get')
            ->willReturnCallback(static fn($service) => match ($service) {
                'config'       => $config,
                'LowPriority'  => $lowPriority,
                'HighPriority' => $highPriority,
                default        => null,
            });

        $pipeline = ($this->factory)($this->container);
        $message  = $this->createStub(ResultInterface::class);

        $result = $pipeline->handle($message);

        static::assertSame($message, $result);
        static::assertSame(['high', 'low'], $executionOrder);
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
    public function invokeRunsEarlyMiddlewareBeforeHandlerAndLateMiddlewareAfter(): void
    {
        $trace   = [];
        $command = new class() implements CommandInterface {};

        $handler = new class($trace) implements CommandHandlerInterface {
            /** @param array<string> $trace */
            public function __construct(
                private array &$trace,
            ) {}

            public function handle(CommandInterface $message): ResultInterface
            {
                $this->trace[] = 'handler';

                return new CommandResult($message, MessageStatus::Success, 'handled');
            }
        };

        $resolverContainer = $this->createStub(ContainerInterface::class);
        $resolverContainer->method('has')
            ->willReturnCallback(
                static fn(string $service): bool => match ($service) {
                    'config', $handler::class => true,
                    default                   => false,
                },
            );
        $resolverContainer->method('get')
            ->willReturnCallback(
                static fn(string $service): mixed => match ($service) {
                    'config' => [
                        MessageBusInterface::class => [
                            ConfigProvider::QUERY_MAP_KEY   => [],
                            ConfigProvider::COMMAND_MAP_KEY => [
                                $command::class => $handler::class,
                            ],
                        ],
                    ],
                    $handler::class => $handler,
                    default         => null,
                },
            );

        $resolver          = new MessageHandlerResolver($resolverContainer);
        $handlerMiddleware = new MessageHandlerMiddleware($resolver, new HandleStrategy());

        $early = new class($trace) implements MiddlewareInterface {
            /** @param array<string> $trace */
            public function __construct(
                private array &$trace,
            ) {}

            #[Override]
            public function process(MessageInterface $message, PipelineHandlerInterface $next): ResultInterface
            {
                $this->trace[] = 'early';

                return $next->handle($message);
            }
        };

        $late = new class($trace) implements MiddlewareInterface {
            /** @param array<string> $trace */
            public function __construct(
                private array &$trace,
            ) {}

            #[Override]
            public function process(MessageInterface $message, PipelineHandlerInterface $next): ResultInterface
            {
                $this->trace[] = 'late';

                return $next->handle($message);
            }
        };

        $config = [
            MessageBusInterface::class => [
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [
                    ['middleware' => 'EarlyMiddleware', 'priority' => 100],
                    ['middleware' => MessageHandlerMiddleware::class, 'priority' => 1],
                    ['middleware' => 'LateMiddleware', 'priority' => -1],
                ],
            ],
        ];

        $this->container->method('has')
            ->willReturnCallback(
                static fn($service) => match ($service) {
                    'config', 'EarlyMiddleware', 'LateMiddleware', MessageHandlerMiddleware::class => true,
                    default                                                                        => false,
                },
            );
        $this->container->method('get')
            ->willReturnCallback(
                static fn($service) => match ($service) {
                    'config'                        => $config,
                    'EarlyMiddleware'               => $early,
                    'LateMiddleware'                => $late,
                    MessageHandlerMiddleware::class => $handlerMiddleware,
                    default                         => null,
                },
            );

        $pipeline = ($this->factory)($this->container);

        $result = $pipeline->handle($command);

        static::assertInstanceOf(ResultInterface::class, $result);
        static::assertSame(['early', 'handler', 'late'], $trace);
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
