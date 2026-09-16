<?php

declare(strict_types=1);

namespace WebwareTest\MessageBus\Command;

use Error;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\Command\NamedCommandTrait;

#[CoversTrait(NamedCommandTrait::class)]
final class NamedCommandTraitTest extends TestCase
{
    #[Test]
    public function getNameFallsBackToClassNameWhenNameNotSet(): void
    {
        $command = new class {
            use NamedCommandTrait;
        };

        static::assertSame($command::class, $command->getName());
    }

    #[Test]
    public function getNameReturnsExplicitlySetName(): void
    {
        $command = new class {
            use NamedCommandTrait;

            public function __construct()
            {
                $this->name = 'custom-command-name';
            }
        };

        static::assertSame('custom-command-name', $command->getName());
    }

    #[Test]
    public function nameIsNotWritableFromOutsideTheClass(): void
    {
        $command = new class {
            use NamedCommandTrait;
        };

        $this->expectException(Error::class);

        $command->name = 'set-from-outside';
    }

    #[Test]
    public function nameIsNullByDefault(): void
    {
        $command = new class {
            use NamedCommandTrait;
        };

        static::assertNull($command->name);
    }
}
