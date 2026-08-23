<?php

declare(strict_types=1);

namespace WebwareTest\MessageBus\Middleware;

use Closure;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\CommandHandlerInterface;
use Webware\MessageBus\Exception\HandlerMethodNotFoundException;
use Webware\MessageBus\MessageHandlerResolverInterface;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\Middleware\MessageHandlerMiddleware;
use Webware\MessageBus\MiddlewareInterface;
use Webware\MessageBus\PipelineHandlerInterface;
use Webware\MessageBus\ResultInterface;
use Webware\MessageBus\Strategy\HandleStrategy;
use Webware\MessageBus\StrategyInterface;

#[CoversClass(MessageHandlerMiddleware::class)]
final class MessageHandlerMiddlewareTest extends TestCase
{
    private MessageHandlerMiddleware $middleware;

    /** @var MessageHandlerResolverInterface&Stub */
    private MessageHandlerResolverInterface $resolver;

    /** @var MessageInterface&Stub */
    private MessageInterface $message;

    private PipelineHandlerInterface $next;

    private CommandHandlerInterface $messageHandler;

    #[Test]
    public function constructorAcceptsResolverAndStrategy(): void
    {
        $resolver   = $this->createStub(MessageHandlerResolverInterface::class);
        $middleware = new MessageHandlerMiddleware($resolver, new HandleStrategy());

        static::assertInstanceOf(MessageHandlerMiddleware::class, $middleware);
    }

    #[Test]
    public function middlewareImplementsCorrectInterfaces(): void
    {
        static::assertInstanceOf(MiddlewareInterface::class, $this->middleware);
    }

    #[Test]
    public function processCallsResolverAndStrategyWithCorrectMessage(): void
    {
        /** @var MessageHandlerResolverInterface&MockObject $resolver */
        $resolver = $this->createMock(MessageHandlerResolverInterface::class);
        /** @var StrategyInterface&MockObject $strategy */
        $strategy   = $this->createMock(StrategyInterface::class);
        $middleware = new MessageHandlerMiddleware($resolver, $strategy);

        $resolver->expects($this->once())
            ->method('resolve')
            ->with(static::identicalTo($this->message))
            ->willReturn($this->messageHandler);

        $strategy->expects($this->once())
            ->method('handlerMethod')
            ->with(static::identicalTo($this->message))
            ->willReturn('handle');

        $middleware->process($this->message, $this->next);
    }

    #[Test]
    public function processResolvesMessageHandlerAndCallsStrategyMethod(): void
    {
        $expectedResult     = $this->createResultStub('test result');
        $intermediateResult = $this->createResultStub('intermediate');

        $messageHandler = $this->createCommandHandler(static fn(): ResultInterface => $intermediateResult);
        $next           = $this->createNextHandler(static fn(): ResultInterface => $expectedResult);

        /** @var MessageHandlerResolverInterface&MockObject $resolver */
        $resolver   = $this->createMock(MessageHandlerResolverInterface::class);
        $middleware = new MessageHandlerMiddleware($resolver, new HandleStrategy());

        $resolver->expects($this->once())
            ->method('resolve')
            ->with($this->message)
            ->willReturn($messageHandler);

        $result = $middleware->process($this->message, $next);

        static::assertSame($expectedResult, $result);
    }

    #[Test]
    public function processReturnsResultFromHandler(): void
    {
        $intermediateResult = $this->createResultStub('intermediate result');
        $expectedResult     = $this->createResultStub('final result');

        $this->resolver->method('resolve')
            ->willReturn($this->createCommandHandler(static fn(): ResultInterface => $intermediateResult));

        $next = $this->createNextHandler(static fn(): ResultInterface => $expectedResult);

        $result = $this->middleware->process($this->message, $next);

        static::assertSame($expectedResult, $result);
        static::assertSame('final result', $result->getResult());
    }

    #[Test]
    public function processThrowsWhenResolvedHandlerLacksStrategyMethod(): void
    {
        /** @var StrategyInterface&Stub $strategy */
        $strategy   = $this->createStub(StrategyInterface::class);
        $middleware = new MessageHandlerMiddleware($this->resolver, $strategy);

        $strategy->method('handlerMethod')->willReturn('missingMethod');

        $this->resolver->method('resolve')->willReturn($this->messageHandler);

        $this->expectException(HandlerMethodNotFoundException::class);

        $middleware->process($this->message, $this->next);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver       = $this->createStub(MessageHandlerResolverInterface::class);
        $this->message        = $this->createStub(MessageInterface::class);
        $this->next           = $this->createNextHandler(fn(): ResultInterface => $this->createResultStub('final'));
        $this->messageHandler = $this->createCommandHandler(fn(): ResultInterface => $this->createResultStub('result'));
        $this->middleware     = new MessageHandlerMiddleware($this->resolver, new HandleStrategy());
    }

    private function createCommandHandler(Closure $callback): CommandHandlerInterface
    {
        return new class($callback) implements CommandHandlerInterface {
            public function __construct(
                private readonly Closure $callback,
            ) {}

            public function handle(MessageInterface $message): ResultInterface
            {
                return ($this->callback)($message);
            }
        };
    }

    private function createNextHandler(Closure $callback): PipelineHandlerInterface
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
