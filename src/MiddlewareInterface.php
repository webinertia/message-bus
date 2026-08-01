<?php

declare(strict_types=1);

namespace Webware\MessageBus;

/** @api */
interface MiddlewareInterface
{
    public function process(
        MessageInterface $message,
        MessageHandlerInterface $handler,
    ): ResultInterface;
}
