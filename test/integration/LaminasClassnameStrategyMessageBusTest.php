<?php

declare(strict_types=1);

namespace Webware\MessageBusIntegrationTest;

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
use Webware\MessageBus\Strategy\ClassnameStrategy;
use Webware\MessageBus\StrategyInterface;

/**
 * @import-type ProviderConfig from ConfigProvider
 */
#[CoversClass(MessageBus::class)]
#[CoversMethod(MessageBus::class, 'handle')]
final class LaminasClassnameStrategyMessageBusTest extends TestCase
{
    private ContainerInterface&ServiceManager $container;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function handleDispatchesToNamedMethod(): void
    {
        $cmdBus  = $this->container->get(MessageBusInterface::class);
        $command = new TestAssets\NamedCommand();
        $result  = $cmdBus->handle($command);

        static::assertInstanceOf(CommandResult::class, $result);
        static::assertSame($command, $result->getCommand());
        static::assertSame(MessageStatus::Success, $result->getStatus());
        static::assertSame('named-command', $result->getResult());
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        /** @var ProviderConfig $config */
        $config                                            = (new ConfigProvider())();
        $dependencies                                      = $config['dependencies'];
        $dependencies['aliases'][StrategyInterface::class] = ClassnameStrategy::class;
        $dependencies['factories']                         += [
            TestAssets\NamedCommandHandler::class => InvokableFactory::class,
            TestAssets\NamedCommand::class        => InvokableFactory::class,
        ];
        $config[MessageBusInterface::class][ConfigProvider::COMMAND_MAP_KEY] = [
            TestAssets\NamedCommand::class => TestAssets\NamedCommandHandler::class,
        ];
        $dependencies['services']['config'] = $config;

        $this->container = new ServiceManager($dependencies);
    }
}
