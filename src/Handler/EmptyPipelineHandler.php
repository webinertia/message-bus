<?php

declare(strict_types=1);

namespace Webware\MessageBus\Handler;

use Override;
use Webware\MessageBus\Exception\MessageException;
use Webware\MessageBus\MessageInterface;
use Webware\MessageBus\PipelineHandlerInterface;
use Webware\MessageBus\ResultInterface;

/**
 * @internal
 */
final readonly class EmptyPipelineHandler implements PipelineHandlerInterface
{
    /**
     * @throws MessageException
     */
    #[Override]
    public function handle(MessageInterface $message): ResultInterface
    {
        if ($message instanceof ResultInterface) {
            return $message;
        }

        throw MessageException::messageNotHandled($message::class);
    }
}
