<?php

declare(strict_types=1);

namespace Webware\MessageBus;

/** @api */
enum MessageStatus implements StatusInterface
{
    case Success;
    case Failure;
}
