<?php

declare(strict_types=1);

namespace Webware\MessageBus;

/** @api */
interface MessageHandlerResolverInterface
{
    public function resolve(MessageInterface $message): CommandHandlerInterface|QueryHandlerInterface;
}
