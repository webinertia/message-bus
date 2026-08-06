<?php

declare(strict_types=1);

namespace Webware\MessageBus\Container;

use Psl\Type;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use SplPriorityQueue;
use Webware\MessageBus\ConfigProvider;
use Webware\MessageBus\Exception;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\MiddlewarePipe;
use Webware\MessageBus\MiddlewarePipelineInterface;

use function array_key_exists;
use function array_map;
use function array_reduce;

/**
 * @import-type ProviderConfig from ConfigProvider
 * @import-type MiddlewarePipeline from ConfigProvider
 * @internal
 */
final readonly class MiddlewarePipeFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @param MiddlewarePipeline $middleware
     */
    private static function pipeMiddleware(
        ContainerInterface $container,
        MiddlewarePipelineInterface $middlewarePipe,
        array $middleware,
    ): MiddlewarePipelineInterface {
        /**
         * Equivalent mutant: falling through with an empty $middleware array
         * reaches the same `return $middlewarePipe;` at the end of this
         * method with no observable side effects, so no test can kill it.
         *
         * @infection-ignore-all
         */
        if ([] === $middleware) {
            return $middlewarePipe;
        }

        /**
         * Create a priority queue from the specifications
         *
         * @var SplPriorityQueue $queue
         * @mago-expect lint:ambiguous-function-call
         * @mago-expect lint:ambiguous-function-call
         */
        $queue = array_reduce(
            array_map(collection_mapper_factory('middleware'), $middleware),
            priority_queue_reducer_factory(),
            new SplPriorityQueue(),
        );

        /** @var array{middleware: class-string, priority?: int} $spec */
        foreach ($queue as $spec) {
            if (! $container->has($spec['middleware'])) {
                continue;
            }

            /** @var MiddlewareInterface $middleware */
            $middleware = $container->get($spec['middleware']);
            $middlewarePipe->pipe($middleware);
        }

        return $middlewarePipe;
    }

    /**
     * @throws Type\Exception\AssertException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): MiddlewarePipelineInterface
    {
        if (! $container->has('config')) {
            throw Exception\ServiceNotFoundException::fromService('config');
        }

        /** @var ProviderConfig $config * */
        $config = $container->get('config');

        if (! array_key_exists(MessageBusInterface::class, $config)) {
            throw Exception\InvalidConfigurationException::forRequiredKey(MessageBusInterface::class, self::class);
        }

        $busConfig      = $config[MessageBusInterface::class];
        $middlewarePipe = new MiddlewarePipe();

        self::pipeMiddleware(
            $container,
            $middlewarePipe,
            $busConfig[ConfigProvider::MIDDLEWARE_PIPELINE_KEY],
        );

        return $middlewarePipe;
    }
}
