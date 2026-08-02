<?php

declare(strict_types=1);

namespace Webware\MessageBus\Container;

use SplPriorityQueue;

use function is_int;

use const PHP_INT_MAX;

/**
 * Reduces pipeline entries into a priority queue; entries with equal
 * priority dequeue in declaration order.
 */
function priority_queue_reducer_factory(): callable
{
    $serial = new class {
        public int $value = PHP_INT_MAX;
    };

    return static function (SplPriorityQueue $queue, array $item) use ($serial): SplPriorityQueue {
        $priority = is_int($item['priority'] ?? null) ? $item['priority'] : 1;
        $queue->insert($item, [$priority, $serial->value--]);

        return $queue;
    };
}
