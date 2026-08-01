<?php

declare(strict_types=1);

namespace Webware\MessageBus;

use Override;

/** @api */
interface QueryHandlerInterface extends MessageHandlerInterface
{
    #[Override]
    public function handle(MessageInterface $message): ResultInterface;
}
