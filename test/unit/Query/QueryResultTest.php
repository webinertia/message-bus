<?php

declare(strict_types=1);

namespace WebwareTest\MessageBus\Query;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\MessageStatus;
use Webware\MessageBus\Query\QueryInterface;
use Webware\MessageBus\Query\QueryResult;
use Webware\MessageBus\Query\QueryResultInterface;

#[CoversClass(QueryResult::class)]
final class QueryResultTest extends TestCase
{
    /** @var QueryInterface&Stub */
    private QueryInterface $query;

    private QueryResult $result;

    #[Test]
    public function getQueryReturnsConstructorQuery(): void
    {
        static::assertSame($this->query, $this->result->getQuery());
    }

    #[Test]
    public function getResultReturnsConstructorResult(): void
    {
        static::assertSame('payload', $this->result->getResult());
    }

    #[Test]
    public function getStatusReturnsConstructorStatus(): void
    {
        static::assertSame(MessageStatus::Failure, $this->result->getStatus());
    }

    #[Test]
    public function implementsQueryResultInterface(): void
    {
        static::assertInstanceOf(QueryResultInterface::class, $this->result);
    }

    protected function setUp(): void
    {
        $this->query  = $this->createStub(QueryInterface::class);
        $this->result = new QueryResult($this->query, MessageStatus::Failure, 'payload');
    }
}
