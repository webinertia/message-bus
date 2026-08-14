<?php

declare(strict_types=1);

namespace Webware\MessageBusTest\TestAssets;

use Webware\MessageBus\Container;
use Webware\MessageBus\Handler\EmptyPipelineHandler;
use Webware\MessageBus\MessageBus;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageHandlerResolver;
use Webware\MessageBus\MessageHandlerResolverInterface;
use Webware\MessageBus\Middleware\MessageHandlerMiddleware;
use Webware\MessageBus\MiddlewarePipe;
use Webware\MessageBus\MiddlewarePipelineInterface;
use Webware\MessageBus\Strategy\ClassnameStrategy;
use Webware\MessageBus\Strategy\HandleStrategy;
use Webware\MessageBus\StrategyInterface;

final class ExpectedConfig
{
    /**
     * @return array<class-string, class-string>
     */
    public static function getExpectedAliases(): array
    {
        return [
            MessageBusInterface::class             => MessageBus::class,
            MiddlewarePipelineInterface::class     => MiddlewarePipe::class,
            MessageHandlerResolverInterface::class => MessageHandlerResolver::class,
            StrategyInterface::class               => HandleStrategy::class,
        ];
    }

    /**
     * @return array{
     *     aliases: array<class-string, class-string>,
     *     factories: array<class-string, class-string>,
     *     invokables: array<class-string, class-string>
     * }
     */
    public static function getExpectedDependencies(): array
    {
        return [
            'aliases'    => self::getExpectedAliases(),
            'factories'  => self::getExpectedFactories(),
            'invokables' => self::getExpectedInvokables(),
        ];
    }

    /**
     * @return array<class-string, class-string>
     */
    public static function getExpectedFactories(): array
    {
        return [
            MessageBus::class               => Container\MessageBusFactory::class,
            MessageHandlerResolver::class   => Container\MessageHandlerResolverFactory::class,
            MiddlewarePipe::class           => Container\MiddlewarePipeFactory::class,
            MessageHandlerMiddleware::class => Container\MessageHandlerMiddlewareFactory::class,
        ];
    }

    /**
     * @return array<class-string, class-string>
     */
    public static function getExpectedInvokables(): array
    {
        return [
            EmptyPipelineHandler::class => EmptyPipelineHandler::class,
            HandleStrategy::class       => HandleStrategy::class,
            ClassnameStrategy::class    => ClassnameStrategy::class,
        ];
    }

    /**
     * @return array<array{middleware: class-string, priority: int}>
     */
    public static function getExpectedMiddleware(): array
    {
        return [
            [
                'middleware' => MessageHandlerMiddleware::class,
                'priority'   => 1,
            ],
        ];
    }
}
