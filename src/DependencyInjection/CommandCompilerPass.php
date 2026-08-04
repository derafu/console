<?php

declare(strict_types=1);

/**
 * Derafu: Console - Symfony Console integration for the Derafu Kernel.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Console\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects all services tagged as console commands and stores their IDs so
 * a console-capable Kernel can retrieve and register them at runtime.
 *
 * Commands are tagged automatically via registerForAutoconfiguration() when
 * they extend Symfony\Component\Console\Command\Command. See
 * ConsoleKernelTrait::configureConsole().
 */
final class CommandCompilerPass implements CompilerPassInterface
{
    /**
     * Tag applied to every service that must be exposed as a console
     * command.
     *
     * @var string
     */
    public const TAG = 'console.command';

    /**
     * Container parameter where the collected command service IDs are
     * stored, so they can be retrieved after the container is compiled.
     *
     * @var string
     */
    public const PARAMETER = 'console.command_ids';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $commandIds = [];

        foreach ($container->findTaggedServiceIds(self::TAG) as $id => $tags) {
            // Ensure each command is publicly retrievable from the compiled
            // container so the kernel can resolve it at runtime.
            $container->getDefinition($id)->setPublic(true);

            $commandIds[] = $id;
        }

        $container->setParameter(self::PARAMETER, $commandIds);
    }
}
