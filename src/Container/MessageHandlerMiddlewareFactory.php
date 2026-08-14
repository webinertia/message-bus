<?php

declare(strict_types=1);

namespace Webware\MessageBus\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\MessageBus\MessageHandlerResolverInterface;
use Webware\MessageBus\Middleware\MessageHandlerMiddleware;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\StrategyInterface;

/**
 * @internal
 */
final readonly class MessageHandlerMiddlewareFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(
        ContainerInterface $container,
    ): MiddlewareInterface&MessageHandlerMiddleware {
        $resolver = $container->get(MessageHandlerResolverInterface::class);
        $strategy = $container->get(StrategyInterface::class);

        return new MessageHandlerMiddleware($resolver, $strategy);
    }
}
