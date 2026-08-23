<?php

declare(strict_types=1);

namespace WebwareTest\MessageBus;

use Closure;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use SplQueue;
use Webware\MessageBus\Exception\MessageException;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\MiddlewarePipe;
use Webware\MessageBus\MiddlewarePipelineInterface;
use Webware\MessageBus\PipelineHandlerInterface;
use Webware\MessageBus\ResultInterface;

#[CoversClass(MiddlewarePipe::class)]
final class MiddlewarePipeTest extends TestCase
{
    private MiddlewarePipe $middlewarePipe;

    /** @var MessageInterface&Stub */
    private MessageInterface $command;

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function cloneCreatesIndependentCopy(): void
    {
        $middleware = $this->createStub(MiddlewareInterface::class);
        $this->middlewarePipe->pipe($middleware);

        $clonedPipe = clone $this->middlewarePipe;

        $newMiddleware = $this->createStub(MiddlewareInterface::class);
        $clonedPipe->pipe($newMiddleware);

        // Original should have 1 middleware
        $originalReflection = new ReflectionClass($this->middlewarePipe);
        $originalProperty   = $originalReflection->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $originalPipeline */
        $originalPipeline = $originalProperty->getValue($this->middlewarePipe);

        // Clone should have 2 middleware
        $clonedReflection = new ReflectionClass($clonedPipe);
        $clonedProperty   = $clonedReflection->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $clonedPipeline */
        $clonedPipeline = $clonedProperty->getValue($clonedPipe);

        static::assertCount(1, $originalPipeline);
        static::assertCount(2, $clonedPipeline);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function constructorInitializesEmptyPipeline(): void
    {
        $reflection = new ReflectionClass($this->middlewarePipe);
        $property   = $reflection->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $pipeline */
        $pipeline = $property->getValue($this->middlewarePipe);

        static::assertInstanceOf(SplQueue::class, $pipeline);
        static::assertTrue($pipeline->isEmpty());
    }

    #[Test]
    public function handleMethodExists(): void
    {
        static::assertInstanceOf(PipelineHandlerInterface::class, $this->middlewarePipe);
    }

    #[Test]
    public function handleSupportsVariousReturnTypes(): void
    {
        $testCases = [
            'string result',
            42,
            ['array', 'result'],
            (object) ['object' => 'result'],
            null,
        ];

        foreach ($testCases as $expectedValue) {
            $expectedResult = $this->createResultStub($expectedValue);

            $middleware = $this->createMock(MiddlewareInterface::class);
            $middleware->expects($this->once())
                ->method('process')
                ->willReturn($expectedResult);

            $pipe = new MiddlewarePipe();
            $pipe->pipe($middleware);

            $result = $pipe->handle($this->command);

            static::assertSame($expectedResult, $result);
        }
    }

    #[Test]
    public function handleWithEmptyPipelineCallsEmptyPipelineHandler(): void
    {
        $this->expectException(MessageException::class);

        $this->middlewarePipe->handle($this->command);
    }

    #[Test]
    public function handleWithMiddlewareCallsMiddleware(): void
    {
        $expectedResult = $this->createResultStub('middleware result');

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with(
                $this->command,
                static::isInstanceOf(PipelineHandlerInterface::class),
            )
            ->willReturn($expectedResult);

        $this->middlewarePipe->pipe($middleware);

        $result = $this->middlewarePipe->handle($this->command);

        static::assertSame($expectedResult, $result);
    }

    #[Test]
    public function middlewareCanShortCircuitPipeline(): void
    {
        $expectedResult = $this->createResultStub('short circuit result');

        $shortCircuitMiddleware = $this->createMock(MiddlewareInterface::class);
        $shortCircuitMiddleware->expects($this->once())
            ->method('process')
            ->willReturn($expectedResult);

        $neverCalledMiddleware = $this->createMock(MiddlewareInterface::class);
        $neverCalledMiddleware->expects($this->never())
            ->method('process');

        $this->middlewarePipe->pipe($shortCircuitMiddleware);
        $this->middlewarePipe->pipe($neverCalledMiddleware);

        $result = $this->middlewarePipe->handle($this->command);

        static::assertSame($expectedResult, $result);
    }

    #[Test]
    public function middlewarePipeImplementsCorrectInterfaces(): void
    {
        static::assertInstanceOf(MiddlewarePipelineInterface::class, $this->middlewarePipe);
        static::assertInstanceOf(MiddlewareInterface::class, $this->middlewarePipe);
        static::assertInstanceOf(PipelineHandlerInterface::class, $this->middlewarePipe);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function multipleMiddlewarePipeInstancesAreIndependent(): void
    {
        $middleware1 = $this->createStub(MiddlewareInterface::class);
        $middleware2 = $this->createStub(MiddlewareInterface::class);

        $pipe1 = new MiddlewarePipe();
        $pipe2 = new MiddlewarePipe();

        $pipe1->pipe($middleware1);
        $pipe2->pipe($middleware2);

        // Check pipe1 has only middleware1
        $reflection1 = new ReflectionClass($pipe1);
        $property1   = $reflection1->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $pipeline1 */
        $pipeline1 = $property1->getValue($pipe1);

        // Check pipe2 has only middleware2
        $reflection2 = new ReflectionClass($pipe2);
        $property2   = $reflection2->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $pipeline2 */
        $pipeline2 = $property2->getValue($pipe2);

        static::assertCount(1, $pipeline1);
        static::assertCount(1, $pipeline2);
        static::assertNotSame($pipeline1, $pipeline2);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function pipeAcceptsDifferentMiddlewareTypes(): void
    {
        $middleware1 = $this->createStub(MiddlewareInterface::class);
        $middleware2 = new class() implements MiddlewareInterface {
            #[Override]
            public function process(MessageInterface $message, PipelineHandlerInterface $handler): ResultInterface
            {
                return $handler->handle($message);
            }
        };

        $this->middlewarePipe->pipe($middleware1);
        $this->middlewarePipe->pipe($middleware2);

        $reflection = new ReflectionClass($this->middlewarePipe);
        $property   = $reflection->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $pipeline */
        $pipeline = $property->getValue($this->middlewarePipe);

        static::assertCount(2, $pipeline);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function pipeAddsMiddlewareToQueue(): void
    {
        $middleware = $this->createStub(MiddlewareInterface::class);

        $this->middlewarePipe->pipe($middleware);

        $reflection = new ReflectionClass($this->middlewarePipe);
        $property   = $reflection->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $pipeline */
        $pipeline = $property->getValue($this->middlewarePipe);

        static::assertFalse($pipeline->isEmpty());
        static::assertCount(1, $pipeline);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function pipeAddsMultipleMiddleware(): void
    {
        $middleware1 = $this->createStub(MiddlewareInterface::class);
        $middleware2 = $this->createStub(MiddlewareInterface::class);
        $middleware3 = $this->createStub(MiddlewareInterface::class);

        $this->middlewarePipe->pipe($middleware1);
        $this->middlewarePipe->pipe($middleware2);
        $this->middlewarePipe->pipe($middleware3);

        $reflection = new ReflectionClass($this->middlewarePipe);
        $property   = $reflection->getProperty('pipeline');

        /** @var SplQueue<MiddlewareInterface> $pipeline */
        $pipeline = $property->getValue($this->middlewarePipe);

        static::assertCount(3, $pipeline);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function pipeMethodExists(): void
    {
        static::assertInstanceOf(MiddlewarePipelineInterface::class, $this->middlewarePipe);
    }

    #[Test]
    public function processMethodExists(): void
    {
        static::assertInstanceOf(MiddlewareInterface::class, $this->middlewarePipe);
    }

    #[Test]
    public function processSupportsVariousReturnTypes(): void
    {
        $testCases = [
            'string result',
            42,
            ['array', 'result'],
            (object) ['object' => 'result'],
            null,
        ];

        foreach ($testCases as $expectedValue) {
            $expectedResult = $this->createResultStub($expectedValue);
            $handler        = $this->createHandler(static fn(): ResultInterface => $expectedResult);

            $result = $this->middlewarePipe->process($this->command, $handler);

            static::assertSame($expectedResult, $result);
        }
    }

    #[Test]
    public function processWithEmptyPipelineCallsHandler(): void
    {
        $expectedResult  = $this->createResultStub('handler result');
        $capturedMessage = null;
        $handler         = $this->createHandler(static function (MessageInterface $message) use (
            &$capturedMessage,
            $expectedResult,
        ): ResultInterface {
            $capturedMessage = $message;

            return $expectedResult;
        });

        $result = $this->middlewarePipe->process($this->command, $handler);

        static::assertSame($this->command, $capturedMessage);
        static::assertSame($expectedResult, $result);
    }

    #[Test]
    public function processWithMiddlewareCallsMiddleware(): void
    {
        $expectedResult = $this->createResultStub('middleware result');

        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with(
                $this->command,
                static::isInstanceOf(PipelineHandlerInterface::class),
            )
            ->willReturn($expectedResult);

        $this->middlewarePipe->pipe($middleware);

        $handler = $this->createStub(PipelineHandlerInterface::class);
        $result  = $this->middlewarePipe->process($this->command, $handler);

        static::assertSame($expectedResult, $result);
    }

    #[Test]
    public function processWithMultipleMiddlewareCallsInOrder(): void
    {
        $executionOrder = [];

        $middleware1 = new class($executionOrder) implements MiddlewareInterface {
            /** @param array<string> $executionOrder */
            public function __construct(
                private array &$executionOrder,
            ) {}

            #[Override]
            public function process(MessageInterface $message, PipelineHandlerInterface $handler): ResultInterface
            {
                $this->executionOrder[] = 'middleware1';

                return $handler->handle($message);
            }
        };

        $middleware2 = new class($executionOrder) implements MiddlewareInterface {
            /** @param array<string> $executionOrder */
            public function __construct(
                /** @phpstan-ignore property.onlyWritten */
                private array &$executionOrder,
            ) {}

            #[Override]
            public function process(MessageInterface $message, PipelineHandlerInterface $handler): ResultInterface
            {
                $this->executionOrder[] = 'middleware2';

                return $handler->handle($message);
            }
        };

        $expectedResult = $this->createResultStub('final result');

        $handler = $this->createHandler(static function (MessageInterface $message) use (
            &$executionOrder,
            $expectedResult,
        ): ResultInterface {
            $executionOrder[] = 'handler';

            return $expectedResult;
        });

        $this->middlewarePipe->pipe($middleware1);
        $this->middlewarePipe->pipe($middleware2);

        $result = $this->middlewarePipe->process($this->command, $handler);

        static::assertSame($expectedResult, $result);
        static::assertEquals(['middleware1', 'middleware2', 'handler'], $executionOrder);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->middlewarePipe = new MiddlewarePipe();
        $this->command        = $this->createStub(MessageInterface::class);
    }

    /**
     * `PipelineHandlerInterface` is a marker interface, so a stub/mock of it cannot
     * have its `handle()` method configured. A concrete anonymous implementation
     * is used instead.
     */
    private function createHandler(Closure $callback): PipelineHandlerInterface
    {
        return new class($callback) implements PipelineHandlerInterface {
            public function __construct(
                private readonly Closure $callback,
            ) {}

            public function handle(MessageInterface $message): ResultInterface
            {
                return ($this->callback)($message);
            }
        };
    }

    private function createResultStub(mixed $value): ResultInterface
    {
        $result = $this->createStub(ResultInterface::class);
        $result->method('getResult')->willReturn($value);

        return $result;
    }
}
