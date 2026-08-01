<?php

declare(strict_types=1);

namespace Webware\MessageBusTest;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webmozart\Assert\Assert;
use Webware\MessageBus\Command\CommandInterface;
use Webware\MessageBus\Command\CommandResult;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\ConfigProvider;
use Webware\MessageBus\Exception\InvalidConfigurationException;
use Webware\MessageBus\Exception\ServiceNotFoundException;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageHandlerResolver;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryInterface;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\QueryHandlerInterface;
use Webware\MessageBus\ResultInterface;
use Webware\MessageBusTest\TestAssets\InMemoryContainer;

#[CoversClass(MessageHandlerResolver::class)]
final class MessageHandlerResolverTest extends TestCase
{
    private InMemoryContainer $container;

    #[Test]
    public function invokeDelegatesToResolve(): void
    {
        $command = new class() implements CommandInterface {};
        $handler = $this->createCommandHandler('invoked result');

        $this->container->set('config', $this->buildConfig(
            commandMap: [$command::class => $handler::class],
        ));
        $this->container->set($handler::class, $handler);

        $resolver = new MessageHandlerResolver($this->container);

        static::assertSame($handler, $resolver($command));
    }

    #[Test]
    public function resolveReturnsMappedCommandHandlerAndItActuallyHandlesTheCommand(): void
    {
        $command = new class() implements CommandInterface {};
        $handler = $this->createCommandHandler('command handled');

        $this->container->set('config', $this->buildConfig(
            commandMap: [$command::class => $handler::class],
        ));
        $this->container->set($handler::class, $handler);

        $resolver = new MessageHandlerResolver($this->container);
        $resolved = $resolver->resolve($command);

        static::assertSame($handler, $resolved);

        $result = $resolved->handle($command);

        static::assertSame('command handled', $result->getResult());
    }

    #[Test]
    public function resolveReturnsMappedQueryHandlerAndItActuallyHandlesTheQuery(): void
    {
        $query   = new class() implements QueryInterface {};
        $handler = $this->createQueryHandler('query handled');

        $this->container->set('config', $this->buildConfig(
            queryMap: [$query::class => $handler::class],
        ));
        $this->container->set($handler::class, $handler);

        $resolver = new MessageHandlerResolver($this->container);
        $resolved = $resolver->resolve($query);

        static::assertSame($handler, $resolved);

        $result = $resolved->handle($query);

        static::assertSame('query handled', $result->getResult());
    }

    #[Test]
    public function resolveSupportsCommandAndQueryMapsConfiguredTogether(): void
    {
        $command        = new class() implements CommandInterface {};
        $query          = new class() implements QueryInterface {};
        $commandHandler = $this->createCommandHandler('command handled');
        $queryHandler   = $this->createQueryHandler('query handled');

        $this->container->set('config', $this->buildConfig(
            commandMap: [$command::class => $commandHandler::class],
            queryMap: [$query::class => $queryHandler::class],
        ));
        $this->container->set($commandHandler::class, $commandHandler);
        $this->container->set($queryHandler::class, $queryHandler);

        $resolver = new MessageHandlerResolver($this->container);

        static::assertSame($commandHandler, $resolver->resolve($command));
        static::assertSame($queryHandler, $resolver->resolve($query));
    }

    #[Test]
    public function resolveThrowsInvalidConfigurationExceptionForUnmappedMessage(): void
    {
        $message = new class() implements MessageInterface {};

        $this->container->set('config', $this->buildConfig());

        $resolver = new MessageHandlerResolver($this->container);

        $this->expectException(InvalidConfigurationException::class);

        $resolver->resolve($message);
    }

    #[Test]
    public function resolveThrowsServiceNotFoundExceptionWhenHandlerIsNotInContainer(): void
    {
        $command = new class() implements CommandInterface {};

        // @mago-expect lint:no-literal-namespace-string
        $this->container->set('config', $this->buildConfig(
            commandMap: [$command::class => 'Not\\Registered\\Handler'],
        ));

        $resolver = new MessageHandlerResolver($this->container);

        $this->expectException(ServiceNotFoundException::class);

        $resolver->resolve($command);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new InMemoryContainer();
    }

    /**
     * @return array{
     *     command_map: array<class-string, class-string>,
     *     query_map: array<class-string, class-string>,
     *     middleware_pipeline: array<array{middleware: class-string, priority?: int}>
     * }
     */
    private function buildConfig(array $commandMap = [], array $queryMap = []): array
    {
        return [
            MessageBusInterface::class => [
                ConfigProvider::COMMAND_MAP_KEY         => $commandMap,
                ConfigProvider::QUERY_MAP_KEY           => $queryMap,
                ConfigProvider::MIDDLEWARE_PIPELINE_KEY => [],
            ],
        ];
    }

    private function createCommandHandler(mixed $resultValue): CommandHandlerInterface
    {
        return new class($resultValue) implements CommandHandlerInterface {
            public function __construct(
                private readonly mixed $resultValue,
            ) {}

            #[Override]
            public function handle(MessageInterface $message): ResultInterface
            {
                Assert::isInstanceOf($message, CommandInterface::class);

                return new CommandResult($message, MessageStatus::Success, $this->resultValue);
            }
        };
    }

    private function createQueryHandler(mixed $resultValue): QueryHandlerInterface
    {
        return new class($resultValue) implements QueryHandlerInterface {
            public function __construct(
                private readonly mixed $resultValue,
            ) {}

            #[Override]
            public function handle(MessageInterface $message): ResultInterface
            {
                Assert::isInstanceOf($message, QueryInterface::class);

                return new QueryResult($message, MessageStatus::Success, $this->resultValue);
            }
        };
    }
}
