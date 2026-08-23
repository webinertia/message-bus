<?php

declare(strict_types=1);

namespace WebwareTest\MessageBus;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\ConfigProvider;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\Middleware\MessageHandlerMiddleware;

#[CoversClass(ConfigProvider::class)]
#[CoversMethod(ConfigProvider::class, '__invoke')]
#[CoversMethod(ConfigProvider::class, 'getDependencies')]
#[CoversMethod(ConfigProvider::class, 'getCommandMap')]
#[CoversMethod(ConfigProvider::class, 'getMiddleware')]
#[CoversMethod(ConfigProvider::class, 'getQueryMap')]
final class ConfigProviderTest extends TestCase
{
    private ConfigProvider $configProvider;

    /** @var array<string, class-string> */
    private array $expectedAliases;

    /** @var array<class-string, class-string> */
    private array $expectedFactories;

    /** @var array<class-string, class-string> */
    private array $expectedInvokables;

    /** @var array<array{middleware: class-string, priority: int}> */
    private array $expectedMiddleware;

    #[Test]
    public function configProviderCanBeInvokedMultipleTimes(): void
    {
        $config1 = ($this->configProvider)();
        $config2 = ($this->configProvider)();

        static::assertSame($config1, $config2);
    }

    #[Test]
    public function constants(): void
    {
        static::assertSame('command_map', ConfigProvider::COMMAND_MAP_KEY);
        static::assertSame('middleware_pipeline', ConfigProvider::MIDDLEWARE_PIPELINE_KEY);
        static::assertSame(1, ConfigProvider::DEFAULT_PRIORITY);
    }

    #[Test]
    public function getCommandMapReturnsEmptyArray(): void
    {
        $commandMap = $this->configProvider->getCommandMap();

        static::assertEmpty($commandMap);
    }

    #[Test]
    public function getDependenciesReturnsCorrectAliases(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        static::assertSame($this->expectedAliases, $dependencies['aliases']);
    }

    #[Test]
    public function getDependenciesReturnsCorrectFactories(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        static::assertSame($this->expectedFactories, $dependencies['factories']);
    }

    #[Test]
    public function getDependenciesReturnsCorrectInvokables(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        static::assertSame($this->expectedInvokables, $dependencies['invokables']);
    }

    #[Test]
    public function getDependenciesStructure(): void
    {
        $dependencies = $this->configProvider->getDependencies();

        static::assertCount(3, $dependencies);
        static::assertArrayHasKey('aliases', $dependencies);
        static::assertArrayHasKey('factories', $dependencies);
        static::assertArrayHasKey('invokables', $dependencies);
    }

    #[Test]
    public function getMiddlewareReturnsDefaultConfiguration(): void
    {
        $middleware = $this->configProvider->getMiddleware();

        static::assertSame($this->expectedMiddleware, $middleware);
    }

    #[Test]
    public function getMiddlewareStructure(): void
    {
        $middleware = $this->configProvider->getMiddleware();
        static::assertArrayHasKey(0, $middleware);
        static::assertArrayHasKey('middleware', $middleware[0]);
        static::assertCount(1, $middleware);
        static::assertSame(MessageHandlerMiddleware::class, $middleware[0]['middleware']);
    }

    #[Test]
    public function getQueryMapReturnsEmptyArray(): void
    {
        $queryMap = $this->configProvider->getQueryMap();

        static::assertEmpty($queryMap);
    }

    #[Test]
    public function invokeReturnsCorrectStructure(): void
    {
        /** @var array{
         *     dependencies: array{
         *         aliases: array<class-string, class-string>,
         *         factories: array<class-string, class-string>,
         *         invokables: array<class-string, class-string>
         *     },
         *     MessageBusInterface::class: array{
         *         command_map: array<class-string, class-string>,
         *         middleware_pipeline: array<array{middleware: class-string, priority?: int}>
         *     }
         * } $config
         */
        $config = ($this->configProvider)();

        static::assertArrayHasKey('dependencies', $config);
        static::assertArrayHasKey(MessageBusInterface::class, $config);

        $dependencies = $config['dependencies'];
        static::assertArrayHasKey('aliases', $dependencies);
        static::assertArrayHasKey('factories', $dependencies);

        /** @var array{
         *     command_map: array<class-string, class-string>,
         *     middleware_pipeline: array<array{middleware: class-string, priority?: int}>
         * } $cmdBusConfig
         */
        $cmdBusConfig = $config[MessageBusInterface::class] ?? [];
        static::assertNotNull($cmdBusConfig);
        static::assertArrayHasKey(ConfigProvider::COMMAND_MAP_KEY, $cmdBusConfig);
        static::assertArrayHasKey(ConfigProvider::MIDDLEWARE_PIPELINE_KEY, $cmdBusConfig);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->configProvider     = new ConfigProvider();
        $this->expectedAliases    = TestAssets\ExpectedConfig::getExpectedAliases();
        $this->expectedFactories  = TestAssets\ExpectedConfig::getExpectedFactories();
        $this->expectedInvokables = TestAssets\ExpectedConfig::getExpectedInvokables();
        $this->expectedMiddleware = TestAssets\ExpectedConfig::getExpectedMiddleware();
    }
}
