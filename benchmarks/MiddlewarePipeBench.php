<?php

declare(strict_types=1);

namespace Webware\MessageBusBench;

use PhpBench\Attributes as Bench;
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\MessageBus;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\Middleware\MessageHandlerMiddleware;
use Webware\MessageBus\MiddlewarePipe;
use Webware\MessageBusBench\Fixtures\BenchCommand;
use Webware\MessageBusBench\Fixtures\BenchCommandHandler;
use Webware\MessageBusBench\Fixtures\PassthroughMiddleware;
use Webware\MessageBusBench\Fixtures\StaticHandlerResolver;

/**
 * Benchmarks dispatching a message through the MessageBus -> MiddlewarePipe pipeline,
 * with a varying number of pass-through middleware ahead of the terminal handler middleware.
 */
#[Bench\BeforeMethods('setUp')]
#[Bench\Revs(1000)]
#[Bench\Iterations(5)]
#[Bench\OutputTimeUnit('microseconds')]
#[Bench\Groups(['pipeline'])]
final class MiddlewarePipeBench
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
        ];
    }

    /**
     * @param array{middlewareCount: int} $params
     */
    public function setUp(array $params): void
    {
        $pipeline = new MiddlewarePipe();

        for ($i = 0; $i < $params['middlewareCount']; ++$i) {
            $pipeline->pipe(new PassthroughMiddleware());
        }

        $resolver = new StaticHandlerResolver(new BenchCommandHandler());
        $pipeline->pipe(new MessageHandlerMiddleware($resolver));

        $this->bus     = new MessageBus($pipeline);
        $this->command = new BenchCommand();
    }
}
