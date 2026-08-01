<?php

declare(strict_types=1);

namespace Webware\MessageBus;

/**
 * @method ResultInterface handle(MessageInterface $message)
 * @internal
 */
interface MessageHandlerInterface
{
    public function handle(MessageInterface $message): ResultInterface;
}
