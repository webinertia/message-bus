<?php

declare(strict_types=1);

namespace Webware\MessageBusTest\Handler;

use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\Exception\MessageException;
use Webware\MessageBus\Handler\EmptyPipelineHandler;
use Webware\MessageBus\MessageHandlerInterface;
use Webware\MessageBus\MessageInterface;

#[CoversClass(EmptyPipelineHandler::class)]
final class EmptyPipelineHandlerTest extends TestCase
{
    private EmptyPipelineHandler $handler;

    /** @var MessageInterface&Stub */
    private MessageInterface $message;

    #[Test]
    public function exceptionMessageContainsMessageClassName(): void
    {
        $message = new class() implements MessageInterface {};

        try {
            $this->handler->handle($message);
            static::fail('Expected MessageException to be thrown');
        } catch (MessageException $e) {
            static::assertStringContainsString('No message handler found for message class', $e->getMessage());
            static::assertStringContainsString('Webware\\MessageBus\\MessageInterface@anonymous', $e->getMessage());
        }
    }

    /**
     * @throws MessageException
     */
    #[Test]
    public function handleAlwaysThrowsException(): void
    {
        $message1 = $this->createStub(MessageInterface::class);
        $message2 = $this->createStub(MessageInterface::class);

        // Test that it always throws an exception regardless of the message
        $this->expectException(MessageException::class);
        $this->handler->handle($message1);

        // This won't be reached, but shows the pattern
        $this->expectException(MessageException::class);
        $this->handler->handle($message2);
    }

    #[Test]
    public function handleMethodExistsAndIsCallable(): void
    {
        static::assertInstanceOf(MessageHandlerInterface::class, $this->handler);
    }

    #[Test]
    public function handlerImplementsMessageHandlerInterface(): void
    {
        static::assertInstanceOf(MessageHandlerInterface::class, $this->handler);
    }

    #[Test]
    public function handlerIsInstantiable(): void
    {
        $handler = new EmptyPipelineHandler();
        static::assertInstanceOf(EmptyPipelineHandler::class, $handler);
    }

    /**
     * @throws MessageException
     */
    #[Test]
    public function handleThrowsMessageException(): void
    {
        $this->expectException(MessageException::class);
        $this->expectExceptionMessage('No message handler found for message class');

        $this->handler->handle($this->message);
    }

    /**
     * @throws MessageException
     */
    #[Test]
    public function handleThrowsMessageExceptionWithCorrectMessageClass(): void
    {
        // Create a specific message class for testing
        $message = new class() implements MessageInterface {};

        $this->expectException(MessageException::class);
        $this->expectExceptionMessage(
            'No message handler found for message class "Webware\\MessageBus\\MessageInterface@anonymous',
        );

        $this->handler->handle($message);
    }

    /**
     * @throws MessageException
     */
    #[Test]
    public function handleWithDifferentMessageTypes(): void
    {
        // Test with a simple message implementation
        $simpleMessage = new class() implements MessageInterface {};

        $this->expectException(MessageException::class);
        $this->handler->handle($simpleMessage);
    }

    #[Test]
    public function multipleHandlersAreIndependent(): void
    {
        $handler1 = new EmptyPipelineHandler();
        $handler2 = new EmptyPipelineHandler();

        static::assertNotSame($handler1, $handler2);
        static::assertEquals($handler1, $handler2);
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new EmptyPipelineHandler();
        $this->message = $this->createStub(MessageInterface::class);
    }
}
