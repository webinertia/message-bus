<?php

declare(strict_types=1);

namespace Webware\MessageBus\Query;

use Override;
use Webware\MessageBus\ResultInterface;
use Webware\MessageBus\StatusInterface;

/**
 * @internal
 */
interface QueryResultInterface extends QueryInterface, ResultInterface
{
    public function getQuery(): QueryInterface;

    #[Override]
    public function getResult(): mixed;

    #[Override]
    public function getStatus(): StatusInterface;
}
