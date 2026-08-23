<?php

declare(strict_types=1);

namespace WebwareTestIntegration\MessageBus;

use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\ConfigProvider;
use Webware\MessageBus\MessageBus;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageStatus;

/**
 * @import-type ProviderConfig from ConfigProvider
 * @import-type MessageBusConfig from ConfigProvider
 * @import-type CommandMap from ConfigProvider
 * @import-type MiddlewarePipeline from ConfigProvider
 */
#[CoversClass(MessageBus::class)]
#[CoversMethod(MessageBus::class, 'handle')]
final class LaminasServiceManagerMessageBusTest extends TestCase
{
    private ContainerInterface&ServiceManager $container;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function handle(): void
    {
        $cmdBus  = $this->container->get(MessageBusInterface::class);
        $command = new TestAssets\Command();
        $result  = $cmdBus->handle($command);

        static::assertInstanceOf(CommandResult::class, $result);
        static::assertSame($command, $result->getCommand());
        static::assertSame(MessageStatus::Success, $result->getStatus());
        static::assertSame('Command-One', $result->getResult());
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        /** @var ProviderConfig $config */
        $config                    = (new ConfigProvider())();
        $dependencies              = $config['dependencies'];
        $dependencies['factories'] += [
            TestAssets\CommandHandler::class       => InvokableFactory::class,
            TestAssets\Command::class              => InvokableFactory::class,
            TestAssets\TestMiddlewareFirst::class  => InvokableFactory::class,
            TestAssets\TestMiddlewareSecond::class => InvokableFactory::class,
        ];
        $config[MessageBusInterface::class][ConfigProvider::COMMAND_MAP_KEY] = [
            TestAssets\Command::class => TestAssets\CommandHandler::class,
        ];
        $middleware     = $config[MessageBusInterface::class][ConfigProvider::MIDDLEWARE_PIPELINE_KEY];
        $testMiddleware = [
            [
                'middleware' => TestAssets\TestMiddlewareFirst::class,
                'priority'   => 100,
            ],
            [
                'middleware' => TestAssets\TestMiddlewareSecond::class,
                'priority'   => -1,
            ],
        ];
        $config[MessageBusInterface::class][ConfigProvider::MIDDLEWARE_PIPELINE_KEY] = [
            ...$middleware,
            ...$testMiddleware,
        ];
        $dependencies['services']['config'] = $config;

        $this->container = new ServiceManager($dependencies);
    }
}
