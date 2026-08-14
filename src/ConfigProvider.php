<?php

declare(strict_types=1);

namespace Webware\MessageBus;

/**
 * @type CommandMap = array<class-string, class-string>
 * @type QueryMap = array<class-string, class-string>
 * @type MiddlewarePipeline = array<array{middleware: class-string, priority?: int}>
 * @type MessageBusConfig = array{
 *    command_map: CommandMap,
 *    query_map: QueryMap,
 *    middleware_pipeline: MiddlewarePipeline
 * }
 * @type Dependencies = array{
 *    aliases: array<string, class-string>,
 *    factories: array<class-string, class-string>,
 *    invokables: array<class-string, class-string>
 * }
 * @type ProviderConfig = array{
 *    dependencies: Dependencies,
 *    Webware\MessageBus\MessageBusInterface: MessageBusConfig,
 * }
 * @internal
 */
final readonly class ConfigProvider
{
    public const string COMMAND_MAP_KEY = 'command_map';

    public const string QUERY_MAP_KEY = 'query_map';

    public const int DEFAULT_PRIORITY = 1;

    public const string MIDDLEWARE_PIPELINE_KEY = 'middleware_pipeline';

    /**
     * @return CommandMap
     */
    public function getCommandMap(): array
    {
        return [
            // Command FQCN => CommandHandler FQCN
        ];
    }

    /**
     * @return Dependencies
     */
    public function getDependencies(): array
    {
        return [
            'aliases'    => [
                MessageBusInterface::class             => MessageBus::class,
                MiddlewarePipelineInterface::class     => MiddlewarePipe::class,
                MessageHandlerResolverInterface::class => MessageHandlerResolver::class,
                StrategyInterface::class               => Strategy\HandleStrategy::class,
            ],
            'factories'  => [
                MessageBus::class                          => Container\MessageBusFactory::class,
                MessageHandlerResolver::class              => Container\MessageHandlerResolverFactory::class,
                MiddlewarePipe::class                      => Container\MiddlewarePipeFactory::class,
                Middleware\MessageHandlerMiddleware::class => Container\MessageHandlerMiddlewareFactory::class,
            ],
            'invokables' => [
                Handler\EmptyPipelineHandler::class => Handler\EmptyPipelineHandler::class,
                Strategy\HandleStrategy::class      => Strategy\HandleStrategy::class,
                Strategy\ClassnameStrategy::class   => Strategy\ClassnameStrategy::class,
            ],
        ];
    }

    /**
     * @return MiddlewarePipeline
     */
    public function getMiddleware(): array
    {
        return [
            [
                'middleware' => Middleware\MessageHandlerMiddleware::class,
                'priority'   => self::DEFAULT_PRIORITY,
            ],
        ];
    }

    /**
     * @return QueryMap
     */
    public function getQueryMap(): array
    {
        return [
            // Query FQCN => QueryHandler FQCN
        ];
    }

    /**
     * @return ProviderConfig
     */
    public function __invoke(): array
    {
        return [
            'dependencies'             => $this->getDependencies(),
            MessageBusInterface::class => [
                self::COMMAND_MAP_KEY         => $this->getCommandMap(),
                self::QUERY_MAP_KEY           => $this->getQueryMap(),
                self::MIDDLEWARE_PIPELINE_KEY => $this->getMiddleware(),
            ],
        ];
    }
}
