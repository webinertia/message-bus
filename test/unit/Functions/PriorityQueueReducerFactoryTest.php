<?php

declare(strict_types=1);

namespace WebwareTest\MessageBus\Functions;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SplPriorityQueue;

use function Webware\MessageBus\Container\priority_queue_reducer_factory;

#[CoversFunction('Webware\MessageBus\Container\priority_queue_reducer_factory')]
final class PriorityQueueReducerFactoryTest extends TestCase
{
    #[Test]
    public function defaultsToPriorityOneWhenNotAnInteger(): void
    {
        $reducer = priority_queue_reducer_factory();
        $queue   = new SplPriorityQueue();

        $item = ['priority' => 'not-an-int', 'label' => 'default'];

        $queue = $reducer($queue, $item);

        static::assertSame($item, $queue->extract());
    }

    #[Test]
    public function equalPriorityItemsDequeueInDeclarationOrder(): void
    {
        $reducer = priority_queue_reducer_factory();
        $queue   = new SplPriorityQueue();

        $first  = ['priority' => 5, 'label' => 'first'];
        $second = ['priority' => 5, 'label' => 'second'];

        $queue = $reducer($queue, $first);
        $queue = $reducer($queue, $second);

        static::assertSame($first, $queue->extract());
        static::assertSame($second, $queue->extract());
    }

    #[Test]
    public function insertsItemsRespectingDeclaredPriority(): void
    {
        $reducer = priority_queue_reducer_factory();
        $queue   = new SplPriorityQueue();

        $low  = ['priority' => 1, 'label' => 'low'];
        $high = ['priority' => 10, 'label' => 'high'];

        $queue = $reducer($queue, $low);
        $queue = $reducer($queue, $high);

        static::assertSame($high, $queue->extract());
        static::assertSame($low, $queue->extract());
    }
}
