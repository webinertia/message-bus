<?php

declare(strict_types=1);

namespace Webware\MessageBus\Query;

use Override;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\StatusInterface;

/**
 * @api
 */
final readonly class QueryResult implements QueryResultInterface
{
    public function __construct(
        private QueryInterface $query,
        private MessageStatus $status,
        private mixed $result,
    ) {}

    #[Override]
    public function getQuery(): QueryInterface
    {
        return $this->query;
    }

    #[Override]
    public function getResult(): mixed
    {
        return $this->result;
    }

    #[Override]
    public function getStatus(): StatusInterface
    {
        return $this->status;
    }
}
