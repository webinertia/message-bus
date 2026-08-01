<?php

declare(strict_types=1);

namespace Webware\MessageBusTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use SplQueue;
use Webware\MessageBus\Exception\MessageException;
use Webware\MessageBus\Exception\NextHandlerAlreadyCalledException;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\Next;
use Webware\MessageBus\ResultInterface;

#[CoversClass(Next::class)]
final class NextTest extends TestCase
{
    /**
     * @throws ReflectionException
     */
    #[Test]
    public function handleClonesNextInstanceWhenProcessingMiddleware(): void
    {
        // Arrange
        $command        = $this->createCommandStub();
        $expectedResult = $this->createResultStub('result');

        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($command, $this->isInstanceOf(Next::class))
            ->willReturn($expectedResult);

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware);
        $originalNext = new Next($queue);

        // Act
        $result = $originalNext->handle($command);

        // Assert
        $this->assertSame($expectedResult, $result);

        // Verify original Next instance queue is marked as processed
        $reflection    = new ReflectionClass($originalNext);
        $queueProperty = $reflection->getProperty('queue');
        $originalQueue = $queueProperty->getValue($originalNext);

        $this->assertNull($originalQueue);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function handleDequeuesMiddlewareFromQueue(): void
    {
        // Arrange
        $command = $this->createCommandStub();

        /** @var MiddlewareInterface&MockObject $middleware1 */
        $middleware1 = $this->createMock(MiddlewareInterface::class);

        /** @var MiddlewareInterface&MockObject $middleware2 */
        $middleware2 = $this->createStub(MiddlewareInterface::class);

        $middleware1->expects($this->once())
            ->method('process')
            ->with($command, $this->isInstanceOf(Next::class))
            ->willReturn($this->createResultStub('result1'));

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware1);
        $queue->enqueue($middleware2);
        $next = new Next($queue);

        // Act
        $next->handle($command);

        // Assert - Use reflection to verify queue state
        $reflection    = new ReflectionClass($next);
        $queueProperty = $reflection->getProperty('queue');
        $internalQueue = $queueProperty->getValue($next);

        // Queue should be null after processing (marked as processed)
        $this->assertNull($internalQueue);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function handleMarksQueueAsProcessedAfterExecution(): void
    {
        // Arrange
        $command = $this->createCommandStub();

        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createStub(MiddlewareInterface::class);
        $middleware->method('process')->willReturn($this->createResultStub('result'));

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware);
        $next = new Next($queue);

        // Act
        $next->handle($command);

        // Assert - Use reflection to verify queue is null
        $reflection    = new ReflectionClass($next);
        $queueProperty = $reflection->getProperty('queue');
        $internalQueue = $queueProperty->getValue($next);

        $this->assertNull($internalQueue);
    }

    #[Test]
    public function handleProcessesMiddlewareAndReturnsResult(): void
    {
        // Arrange
        $command        = $this->createCommandStub();
        $expectedResult = $this->createResultStub('test_result');

        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($command, $this->isInstanceOf(Next::class))
            ->willReturn($expectedResult);

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware);
        $next = new Next($queue);

        // Act
        $result = $next->handle($command);

        // Assert
        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    public function handleSupportsNullReturnFromMiddleware(): void
    {
        // Arrange
        $command        = $this->createCommandStub();
        $expectedResult = $this->createResultStub(null);

        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->expects($this->once())
            ->method('process')
            ->with($command, $this->isInstanceOf(Next::class))
            ->willReturn($expectedResult);

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware);
        $next = new Next($queue);

        // Act
        $result = $next->handle($command);

        // Assert
        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    public function handleSupportsVariousReturnTypes(): void
    {
        $testCases = [
            'string'        => 'test_string',
            'integer'       => 42,
            'float'         => 3.14,
            'boolean_true'  => true,
            'boolean_false' => false,
            'array'         => [
                'key' => 'value',
            ],
            'object'        => (object) ['property' => 'value'],
        ];

        foreach ($testCases as $description => $expectedValue) {
            // Arrange
            $command        = $this->createCommandStub();
            $expectedResult = $this->createResultStub($expectedValue);

            /** @var MiddlewareInterface&MockObject $middleware */
            $middleware = $this->createMock(MiddlewareInterface::class);
            $middleware->expects($this->once())
                ->method('process')
                ->with($command, $this->isInstanceOf(Next::class))
                ->willReturn($expectedResult);

            $queue = $this->createMiddlewareQueue();
            $queue->enqueue($middleware);
            $next = new Next($queue);

            // Act
            $result = $next->handle($command);

            // Assert
            $this->assertSame($expectedResult, $result, "Failed for test case: {$description}");
        }
    }

    #[Test]
    public function handleThrowsExceptionWhenQueueAlreadyProcessed(): void
    {
        // Arrange
        $command = $this->createCommandStub();

        /** @var MiddlewareInterface&MockObject $middleware */
        $middleware = $this->createStub(MiddlewareInterface::class);
        $middleware->method('process')->willReturn($this->createResultStub('result'));

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware);
        $next = new Next($queue);

        // Process once to mark as processed
        $next->handle($command);

        // Assert
        $this->expectException(NextHandlerAlreadyCalledException::class);
        $this->expectExceptionMessage('The next handler has already been called.');

        // Act - Try to process again
        $next->handle($command);
    }

    #[Test]
    public function handleThrowsExceptionWhenQueueIsEmpty(): void
    {
        // Arrange
        $command    = $this->createCommandStub();
        $emptyQueue = $this->createMiddlewareQueue();
        $next       = new Next($emptyQueue);

        // Assert
        $this->expectException(MessageException::class);
        $this->expectExceptionMessage('No message handler found for message class');

        // Act
        $next->handle($command);
    }

    /**
     * @throws ReflectionException
     */
    #[Test]
    public function handleWorksWithMultipleMiddlewareInSequence(): void
    {
        // Arrange
        $command        = $this->createCommandStub();
        $expectedResult = $this->createResultStub('result1');

        /** @var MiddlewareInterface&MockObject $middleware1 */
        $middleware1 = $this->createMock(MiddlewareInterface::class);

        /** @var MiddlewareInterface&MockObject $middleware2 */
        $middleware2 = $this->createStub(MiddlewareInterface::class);

        /** @var MiddlewareInterface&MockObject $middleware3 */
        $middleware3 = $this->createStub(MiddlewareInterface::class);

        $middleware1->expects($this->once())
            ->method('process')
            ->with($command, $this->isInstanceOf(Next::class))
            ->willReturn($expectedResult);

        $queue = $this->createMiddlewareQueue();
        $queue->enqueue($middleware1);
        $queue->enqueue($middleware2);
        $queue->enqueue($middleware3);

        $next = new Next($queue);

        // Act
        $result = $next->handle($command);

        // Assert
        $this->assertSame($expectedResult, $result);

        // Verify only first middleware was processed and queue is now null
        $reflection    = new ReflectionClass($next);
        $queueProperty = $reflection->getProperty('queue');
        $internalQueue = $queueProperty->getValue($next);

        $this->assertNull($internalQueue);
    }

    /**
     * Creates a stub MessageInterface instance
     */
    private function createCommandStub(): MessageInterface
    {
        return $this->createStub(MessageInterface::class);
    }

    private function createMiddlewareQueue(): SplQueue
    {
        return new SplQueue();
    }

    private function createResultStub(mixed $value): ResultInterface
    {
        $result = $this->createStub(ResultInterface::class);
        $result->method('getResult')->willReturn($value);

        return $result;
    }
}
