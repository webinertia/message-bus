<?php

declare(strict_types=1);

namespace Webware\MessageBus\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Webware\MessageBus\Exception\ServiceNotFoundException;
use Webware\MessageBus\MessageBus;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MiddlewarePipe;
use Webware\MessageBus\MiddlewarePipelineInterface;

/**
 * @internal
 */
final readonly class MessageBusFactory
{
    /**
     * @throws ServiceNotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): MessageBusInterface
    {
        if (! $container->has(MiddlewarePipelineInterface::class)) {
            throw ServiceNotFoundException::fromService(MiddlewarePipelineInterface::class);
        }

        /** @var MiddlewarePipe&MiddlewarePipelineInterface $middlewarePipeline */
        $middlewarePipeline = $container->get(MiddlewarePipelineInterface::class);

        return new MessageBus($middlewarePipeline);
    }
}
