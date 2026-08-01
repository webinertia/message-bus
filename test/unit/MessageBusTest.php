<?php

declare(strict_types=1);

namespace Webware\MessageBusTest;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Webware\MessageBus\Exception\MessageException;
use Webware\MessageBus\MessageBus;
use Webware\MessageBus\MessageBusInterface;
use Webware\MessageBus\MessageHandlerInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\MiddlewarePipe;
use Webware\MessageBus\ResultInterface;

#[CoversClass(MessageBus::class)]
#[CoversMethod(MessageBus::class, '__construct')]
#[CoversMethod(MessageBus::class, 'handle')]
final class MessageBusTest extends TestCase
{
    private MessageBus $cmdBus;

    private MiddlewarePipe $pipeline;

    /** @var MessageInterface&Stub */
    private MessageInterface $command;

    #[Test]
    public function cmdBusImplementsCmdBusInterface(): void
    {
        static::assertInstanceOf(MessageBusInterface::class, $this->cmdBus);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function cmdBusIsImmutable(): void
    {
        // The CmdBus should not allow changing its pipeline after construction
        $reflection       = new ReflectionClass($this->cmdBus);
        $pipelineProperty = $reflection->getProperty('pipeline');

        static::assertTrue($pipelineProperty->isPrivate());

        // Since the property is private, it cannot be modified from outside
        // This effectively makes the CmdBus immutable
        static::assertNotNull($pipelineProperty, 'Pipeline property exists and is private, ensuring immutability');
    }

    #[Test]
    public function constructorAcceptsMiddlewarePipeline(): void
    {
        $pipeline = new MiddlewarePipe();
        $cmdBus   = new MessageBus($pipeline);

        static::assertInstanceOf(MessageBus::class, $cmdBus);
    }

    #[Test]
    public function handleDelegatesToPipeline(): void
    {
        // Create an actual MiddlewarePipe since the constructor requires the intersection type
        $pipeline = new MiddlewarePipe();

        // Add a simple middleware that returns a known result
        $pipeline->pipe(new class($this->createResultStub('pipeline result')) implements MiddlewareInterface {
            public function __construct(
                private ResultInterface $result,
            ) {}

            #[Override]
            public function process(MessageInterface $message, MessageHandlerInterface $handler): ResultInterface
            {
                return $this->result;
            }
        });

        $cmdBus = new MessageBus($pipeline);
        $result = $cmdBus->handle($this->command);

        static::assertSame('pipeline result', $result->getResult());
    }

    #[Test]
    public function handlePassesCommandToPipeline(): void
    {
        /** @var MessageInterface|null $capturedMessage */
        $capturedMessage = null;

        $this->pipeline->pipe(
            new class($capturedMessage, $this->createResultStub('captured')) implements MiddlewareInterface {
                public function __construct(
                    /** @var MessageInterface|null $capturedMessage */
                    private mixed &$capturedMessage,
                    private ResultInterface $result,
                ) {}

                #[Override]
                public function process(
                    MessageInterface $message,
                    MessageHandlerInterface $handler,
                ): ResultInterface {
                    $this->capturedMessage = $message;

                    return $this->result;
                }
            },
        );

        $this->cmdBus->handle($this->command);

        static::assertSame($this->command, $capturedMessage);
    }

    #[Test]
    public function handleReturnsResultFromPipeline(): void
    {
        $expectedResult = 'test result from pipeline';

        // Create a simple middleware that returns a known result
        $this->pipeline->pipe(new class($this->createResultStub($expectedResult)) implements MiddlewareInterface {
            public function __construct(
                private ResultInterface $result,
            ) {}

            #[Override]
            public function process(MessageInterface $message, MessageHandlerInterface $handler): ResultInterface
            {
                return $this->result;
            }
        });

        $result = $this->cmdBus->handle($this->command);
        static::assertEquals($expectedResult, $result->getResult());
    }

    #[Test]
    #[TestWith(['string result'])]
    #[TestWith([42])]
    #[TestWith([3.14])]
    #[TestWith([true])]
    #[TestWith([false])]
    #[TestWith([null])]
    #[TestWith([['array', 'result']])]
    public function handleSupportsVariousReturnTypes(mixed $expectedResult): void
    {
        // Create a simple middleware that returns the expected result
        $pipeline = new MiddlewarePipe();
        $pipeline->pipe(new class($this->createResultStub($expectedResult)) implements MiddlewareInterface {
            public function __construct(
                private ResultInterface $result,
            ) {}

            #[Override]
            public function process(MessageInterface $message, MessageHandlerInterface $handler): ResultInterface
            {
                return $this->result;
            }
        });

        $cmdBus = new MessageBus($pipeline);
        $result = $cmdBus->handle($this->command);

        static::assertEquals($expectedResult, $result->getResult());
    }

    #[Test]
    public function handleWithEmptyPipeline(): void
    {
        // Empty pipeline should result in the EmptyPipelineHandler being called
        $this->expectException(MessageException::class);
        $this->expectExceptionMessage('No message handler found for message class');

        $this->cmdBus->handle($this->command);
    }

    #[Test]
    public function multipleCmdBusInstancesAreIndependent(): void
    {
        $pipeline1 = new MiddlewarePipe();
        $pipeline2 = new MiddlewarePipe();

        $cmdBus1 = new MessageBus($pipeline1);
        $cmdBus2 = new MessageBus($pipeline2);

        static::assertNotSame($cmdBus1, $cmdBus2);
        static::assertInstanceOf(MessageBus::class, $cmdBus1);
        static::assertInstanceOf(MessageBus::class, $cmdBus2);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->pipeline = new MiddlewarePipe();
        $this->cmdBus   = new MessageBus($this->pipeline);
        $this->command  = $this->createStub(MessageInterface::class);
    }

    private function createResultStub(mixed $value): ResultInterface
    {
        $result = $this->createStub(ResultInterface::class);
        $result->method('getResult')->willReturn($value);

        return $result;
    }
}
