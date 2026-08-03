<?php

declare(strict_types=1);

namespace Webware\MessageBusTest\Middleware;

use Closure;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageHandlerInterface;
use Webware\MessageBus\MessageHandlerResolverInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\Middleware\MessageHandlerMiddleware;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\ResultInterface;

#[CoversClass(MessageHandlerMiddleware::class)]
final class MessageHandlerMiddlewareTest extends TestCase
{
    private MessageHandlerMiddleware $middleware;

    /** @var MessageHandlerResolverInterface&Stub */
    private MessageHandlerResolverInterface $resolver;

    /** @var MessageInterface&Stub */
    private MessageInterface $message;

    private MessageHandlerInterface $handler;

    private MessageHandlerInterface $messageHandler;

    #[Test]
    public function constructorAcceptsMessageHandlerResolver(): void
    {
        $resolver   = $this->createStub(MessageHandlerResolverInterface::class);
        $middleware = new MessageHandlerMiddleware($resolver);

        static::assertInstanceOf(MessageHandlerMiddleware::class, $middleware);
    }

    #[Test]
    public function middlewareImplementsCorrectInterfaces(): void
    {
        static::assertInstanceOf(MiddlewareInterface::class, $this->middleware);
    }

    #[Test]
    public function processCallsResolverWithCorrectMessage(): void
    {
        /** @var MessageHandlerResolverInterface&MockObject $resolver */
        $resolver   = $this->createMock(MessageHandlerResolverInterface::class);
        $middleware = new MessageHandlerMiddleware($resolver);

        $resolver->expects($this->once())
            ->method('resolve')
            ->with(static::identicalTo($this->message))
            ->willReturn($this->messageHandler);

        $middleware->process($this->message, $this->handler);
    }

    #[Test]
    public function processResolvesMessageHandlerAndCallsIt(): void
    {
        $expectedResult     = $this->createResultStub('test result');
        $intermediateResult = $this->createResultStub('intermediate');

        $messageHandler = $this->createHandler(static fn(): ResultInterface => $intermediateResult);
        $handler        = $this->createHandler(static fn(): ResultInterface => $expectedResult);

        /** @var MessageHandlerResolverInterface&MockObject $resolver */
        $resolver   = $this->createMock(MessageHandlerResolverInterface::class);
        $middleware = new MessageHandlerMiddleware($resolver);

        $resolver->expects($this->once())
            ->method('resolve')
            ->with($this->message)
            ->willReturn($messageHandler);

        $result = $middleware->process($this->message, $handler);

        static::assertSame($expectedResult, $result);
    }

    #[Test]
    public function processReturnsResultFromHandler(): void
    {
        $intermediateResult = $this->createResultStub('intermediate result');
        $expectedResult     = $this->createResultStub('final result');

        $this->resolver->method('resolve')
            ->willReturn($this->createHandler(static fn(): ResultInterface => $intermediateResult));

        $handler = $this->createHandler(static fn(): ResultInterface => $expectedResult);

        $result = $this->middleware->process($this->message, $handler);

        static::assertSame($expectedResult, $result);
        static::assertSame('final result', $result->getResult());
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver       = $this->createStub(MessageHandlerResolverInterface::class);
        $this->message        = $this->createStub(MessageInterface::class);
        $this->handler        = $this->createHandler(fn(): ResultInterface => $this->createResultStub('final'));
        $this->messageHandler = $this->createHandler(fn(): ResultInterface => $this->createResultStub('result'));
        $this->middleware     = new MessageHandlerMiddleware($this->resolver);
    }

    /**
     * `MessageHandlerInterface` is a marker interface, so a stub/mock of it cannot
     * have its `handle()` method configured. A concrete anonymous implementation
     * is used instead.
     */
    private function createHandler(Closure $callback): MessageHandlerInterface
    {
        return new class($callback) implements MessageHandlerInterface {
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
