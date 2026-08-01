<?php

declare(strict_types=1);

namespace Webware\MessageBus\Container;

use Psr\Container\ContainerInterface;
use Webware\MessageBus\MessageHandlerResolver;
use Webware\MessageBus\MessageHandlerResolverInterface;

/**
 * @internal
 */
final readonly class MessageHandlerResolverFactory
{
    public function __invoke(ContainerInterface $container): MessageHandlerResolverInterface
    {
        return new MessageHandlerResolver($container);
    }
}
