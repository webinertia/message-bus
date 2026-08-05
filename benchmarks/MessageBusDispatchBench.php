<?php

declare(strict_types=1);

namespace Webware\MessageBusBench;

use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use PhpBench\Attributes as Bench;
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\ConfigProvider;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBusBench\Fixtures\BenchCommand;
use Webware\MessageBusBench\Fixtures\BenchCommandHandler;
use Webware\MessageBusBench\Fixtures\PassthroughMiddleware;

use function array_fill;

/**
 * @import-type ProviderConfig from ConfigProvider
 *
 * Benchmarks the total, real-world cost of dispatching a command through a container-backed
 * MessageBus: MessageBus -> MiddlewarePipe -> [pass-through middleware...] -> MessageHandlerMiddleware
 * -> (container-based) MessageHandlerResolver -> handler, wired exactly as a consuming application
 * would configure it (via the `middleware_pipeline` config key). Unlike MiddlewarePipeBench, handler
 * resolution goes through a real PSR-11 container (Laminas ServiceManager) on every call.
 */
#[Bench\BeforeMethods('setUp')]
#[Bench\Revs(1000)]
#[Bench\Iterations(5)]
#[Bench\OutputTimeUnit('microseconds')]
#[Bench\Groups(['pipeline'])]
final class MessageBusDispatchBench
{
    private MessageBusInterface $bus;

    private CommandInterface $command;

    #[Bench\ParamProviders('provideMiddlewareCounts')]
    public function benchHandle(array $params): void
    {
        $this->bus->handle($this->command);
    }

    /**
     * @return array<string, array{middlewareCount: int}>
     */
    public function provideMiddlewareCounts(): array
    {
        return [
            '1 middleware' => ['middlewareCount' => 1],
            '5 middleware' => ['middlewareCount' => 5],
        ];
    }

    /**
     * @param array{middlewareCount: int} $params
     */
    public function setUp(array $params): void
    {
        /** @var ProviderConfig $config */
        $config                    = (new ConfigProvider())();
        $dependencies              = $config['dependencies'];
        $dependencies['factories'] += [
            BenchCommandHandler::class => InvokableFactory::class,
            BenchCommand::class        => InvokableFactory::class,
        ];
        $dependencies['invokables'][PassthroughMiddleware::class] = PassthroughMiddleware::class;

        $config[MessageBusInterface::class][ConfigProvider::COMMAND_MAP_KEY] = [
            BenchCommand::class => BenchCommandHandler::class,
        ];
        $config[MessageBusInterface::class][ConfigProvider::MIDDLEWARE_PIPELINE_KEY] = [
            ...array_fill(0, $params['middlewareCount'], [
                'middleware' => PassthroughMiddleware::class,
                'priority'   => 10,
            ]),
            ...$config[MessageBusInterface::class][ConfigProvider::MIDDLEWARE_PIPELINE_KEY],
        ];
        $dependencies['services']['config'] = $config;

        $container = new ServiceManager($dependencies);

        $this->bus     = $container->get(MessageBusInterface::class);
        $this->command = new BenchCommand();
    }
}
