<?php

declare(strict_types=1);

namespace Webware\MessageBusTest\Functions;

use App\FooHandler;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\Exception\InvalidConfigurationException;

use function Webware\MessageBus\Container\collection_mapper_factory;

#[CoversFunction('Webware\MessageBus\Container\collection_mapper_factory')]
final class CollectionMapperFactoryTest extends TestCase
{
    #[Test]
    public function returnsItemUnchangedWhenKeyIsPresent(): void
    {
        $mapper = collection_mapper_factory('handler');
        $item   = ['handler' => FooHandler::class];

        static::assertSame($item, $mapper($item));
    }

    #[Test]
    public function throwsWhenKeyIsMissing(): void
    {
        $mapper = collection_mapper_factory('handler');

        $this->expectException(InvalidConfigurationException::class);

        $mapper(['other' => 'value']);
    }
}
