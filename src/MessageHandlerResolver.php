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
     * @throws ServiceNotFoundException
     */
    #[Override]
    public function resolve(MessageInterface $message): MessageHandlerInterface
    {
        if (! $this->container->has('config')) {
            throw ServiceNotFoundException::fromService('config');
        }

        /** @var ProviderConfig $config */
        $config = $this->container->get('config');

        $cmdBusConfig = $config[MessageBusInterface::class];

        /**
         * Queries are checked first: reads are expected to outnumber writes.
         *
         * @var array{0: class-string, 1: class-string<CommandHandlerInterface|QueryHandlerInterface>} $resolved
         */
        $resolved = match (true) {
            array_key_exists($message::class, $cmdBusConfig[ConfigProvider::QUERY_MAP_KEY]) => [
                $cmdBusConfig[ConfigProvider::QUERY_MAP_KEY][$message::class],
                QueryHandlerInterface::class,
            ],
            array_key_exists($message::class, $cmdBusConfig[ConfigProvider::COMMAND_MAP_KEY]) => [
                $cmdBusConfig[ConfigProvider::COMMAND_MAP_KEY][$message::class],
                CommandHandlerInterface::class,
            ],
            default => throw InvalidConfigurationException::fromUnMappedMessage($message::class),
        };
        [$handlerClass, $expectedType] = $resolved;

        if (! $this->container->has($handlerClass)) {
            throw ServiceNotFoundException::fromService($handlerClass);
        }

        /** @var mixed $handler */
        $handler = $this->container->get($handlerClass);

        if (! $handler instanceof $expectedType) {
            throw InvalidConfigurationException::fromInvalidHandler($handlerClass, $handler, $expectedType);
        }

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
