<?php

declare(strict_types=1);

/**
 * Derafu: Console - Symfony Console integration for the Derafu Kernel.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsConsole\Fixture;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A minimal command used as a fixture to test command auto-discovery.
 */
#[AsCommand(name: 'app:greet', description: 'Prints a greeting.')]
final class GreetCommand extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hello, Derafu Console!');

        return Command::SUCCESS;
    }
}
