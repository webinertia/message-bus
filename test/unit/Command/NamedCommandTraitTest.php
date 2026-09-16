<?php

declare(strict_types=1);

namespace WebwareTest\MessageBus\Command;

use Error;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\MessageBus\Command\NamedCommandInterface;
use Webware\MessageBus\Command\NamedCommandTrait;

#[CoversTrait(NamedCommandTrait::class)]
final class NamedCommandTraitTest extends TestCase
{
    #[Test]
    public function commandNameDefaultsToTheClassName(): void
    {
        $command = new class {
            use NamedCommandTrait;
        };

        static::assertSame($command::class, $command->commandName);
    }

    #[Test]
    public function commandNameIsNotWritableFromOutsideTheClass(): void
    {
        $command = new class {
            use NamedCommandTrait;
        };

        $this->expectException(Error::class);

        $command->commandName = 'set-from-outside';
    }

    #[Test]
    public function getCommandNameDefaultsToClassNameWhenNameNotSet(): void
    {
        $command = new class {
            use NamedCommandTrait;
        };

        static::assertSame($command::class, $command->getCommandName());
    }

    #[Test]
    public function getCommandNameReturnsExplicitlySetName(): void
    {
        $command = new class {
            use NamedCommandTrait;

            public function __construct()
            {
                $this->commandName = 'custom-command-name';
            }
        };

        static::assertSame('custom-command-name', $command->getCommandName());
    }

    #[Test]
    public function traitSatisfiesTheNamedCommandInterfaceContract(): void
    {
        $command = new class implements NamedCommandInterface {
            use NamedCommandTrait;
        };

        static::assertSame($command::class, $command->commandName);
        static::assertSame($command::class, $command->getCommandName());
    }
}
