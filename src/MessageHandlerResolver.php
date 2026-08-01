<?php

declare(strict_types=1);

namespace Webware\MessageBus;

use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Webware\MessageBus\Exception\InvalidConfigurationException;
use Webware\MessageBus\Exception\ServiceNotFoundException;

use function array_key_exists;

/**
 * @import-type ProviderConfig from ConfigProvider
 */
final readonly class MessageHandlerResolver implements MessageHandlerResolverInterface
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidConfigurationException
     */
    #[Override]
    public function resolve(MessageInterface $message): MessageHandlerInterface
    {
        /** @var ProviderConfig $config */
        $config = $this->container->get('config');

        $cmdBusConfig = $config[MessageBusInterface::class];

        $mergedMap = [
            ...$cmdBusConfig[ConfigProvider::COMMAND_MAP_KEY],
            ...$cmdBusConfig[ConfigProvider::QUERY_MAP_KEY],
        ];

        if (
            ! array_key_exists(
                $message::class,
                $mergedMap,
            )
        ) {
            throw InvalidConfigurationException::fromUnMappedMessage($message::class);
        }

        $handlerClass = $mergedMap[$message::class];

        if (! $this->container->has($handlerClass)) {
            throw ServiceNotFoundException::fromService($handlerClass);
        }

        /**
         * @var MessageHandlerInterface $handler
         * @mago-expect lint:inline-variable-return
         */
        $handler = $this->container->get($handlerClass);
        return $handler;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidConfigurationException
     */
    public function __invoke(MessageInterface $message): MessageHandlerInterface
    {
        return $this->resolve($message);
    }
}
