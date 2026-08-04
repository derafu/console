<?php

declare(strict_types=1);

/**
 * Derafu: Console - Symfony Console integration for the Derafu Kernel.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsConsole\DependencyInjection;

use Derafu\Console\DependencyInjection\CommandCompilerPass;
use Derafu\TestsConsole\Fixture\GreetCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(CommandCompilerPass::class)]
final class CommandCompilerPassTest extends TestCase
{
    #[Test]
    public function collectsTaggedCommandServiceIdsAndMakesThemPublic(): void
    {
        $container = new ContainerBuilder();

        $container->register('app.command.greet', GreetCommand::class)
            ->setPublic(false)
            ->addTag(CommandCompilerPass::TAG)
        ;

        $container->register('app.service.unrelated', stdClass::class)
            ->setPublic(false)
        ;

        (new CommandCompilerPass())->process($container);

        $this->assertSame(
            ['app.command.greet'],
            $container->getParameter(CommandCompilerPass::PARAMETER)
        );
        $this->assertTrue(
            $container->getDefinition('app.command.greet')->isPublic()
        );
        $this->assertFalse(
            $container->getDefinition('app.service.unrelated')->isPublic()
        );
    }

    #[Test]
    public function setsAnEmptyParameterWhenNoCommandsAreTagged(): void
    {
        $container = new ContainerBuilder();

        (new CommandCompilerPass())->process($container);

        $this->assertSame(
            [],
            $container->getParameter(CommandCompilerPass::PARAMETER)
        );
    }
}
