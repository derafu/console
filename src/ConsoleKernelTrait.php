<?php

declare(strict_types=1);

/**
 * Derafu: Console - Symfony Console integration for the Derafu Kernel.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Console;

use Derafu\Console\DependencyInjection\CommandCompilerPass;
use Symfony\Component\Console\Application as SymfonyConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds Symfony Console command auto-discovery and execution to a Derafu
 * Kernel.
 *
 * This trait is intentionally scoped to Derafu kernels: it requires the
 * using class to provide a `getContainer(): ContainerInterface` method,
 * which is already available on any class extending
 * Derafu\Kernel\MicroKernel (or implementing an equivalent contract). It is
 * not meant to be used on arbitrary, unrelated classes.
 *
 * Usage:
 *
 *   class MyKernel extends \Derafu\Kernel\MicroKernel
 *   {
 *       use ConsoleKernelTrait;
 *
 *       protected function configure(
 *           ContainerConfigurator $configurator,
 *           ContainerBuilder $container
 *       ): void {
 *           $this->configureConsole($container);
 *       }
 *   }
 *
 *   $kernel = new MyKernel('dev', true);
 *   exit($kernel->run());
 *
 * Whether a project reuses this trait on its main application Kernel, on a
 * dedicated console-only subclass of it, or on a brand new Kernel that only
 * extends Derafu\Kernel\MicroKernel, is entirely up to that project. This
 * trait does not have, and must not have, an opinion about it.
 */
trait ConsoleKernelTrait
{
    /**
     * Gets the dependency injection container.
     *
     * Provided by Derafu\Kernel\MicroKernel. Declared here as abstract so
     * this trait can only be used on classes that actually provide it.
     *
     * @return ContainerInterface
     */
    abstract protected function getContainer(): ContainerInterface;

    /**
     * Registers console command auto-discovery in the container.
     *
     * Any service whose class extends Symfony\Component\Console\Command\Command
     * is automatically tagged and will be available to run() once the
     * container is compiled. Call this method from the Kernel's own
     * configure() method.
     *
     * @param ContainerBuilder $container The container builder.
     * @return void
     */
    protected function configureConsole(ContainerBuilder $container): void
    {
        $container
            ->registerForAutoconfiguration(Command::class)
            ->addTag(CommandCompilerPass::TAG)
        ;

        $container->addCompilerPass(new CommandCompilerPass());
    }

    /**
     * Boots the kernel, builds the Symfony Console Application with every
     * discovered command, and runs it.
     *
     * The $input and $output parameters exist so this method can be tested
     * without depending on the real STDIN/STDOUT/$argv of the process. When
     * omitted, Symfony Console falls back to the actual process arguments
     * and standard streams, which is the desired behavior in a real
     * bin/console entry point.
     *
     * @param InputInterface|null $input Input to use instead of $argv.
     * @param OutputInterface|null $output Output to use instead of STDOUT.
     * @return int The exit code returned by symfony/console.
     */
    public function run(
        ?InputInterface $input = null,
        ?OutputInterface $output = null
    ): int {
        $container = $this->getContainer();

        $application = $this->createConsoleApplication($container);

        foreach ($container->getParameter(CommandCompilerPass::PARAMETER) as $id) {
            $application->addCommand($container->get($id));
        }

        return $application->run($input, $output);
    }

    /**
     * Creates the Symfony Console Application instance.
     *
     * Override this method to customize the name, version, or any other
     * Application setting.
     *
     * The Application is created with auto-exit disabled: this method
     * (and run()) return the exit code instead of terminating the process
     * directly, leaving that decision to the actual entry point script
     * (e.g. bin/console) and keeping run() safe to call from tests.
     *
     * @param ContainerInterface $container The compiled container.
     * @return SymfonyConsoleApplication
     */
    protected function createConsoleApplication(
        ContainerInterface $container
    ): SymfonyConsoleApplication {
        $name = $container->hasParameter('kernel.app_name')
            ? (string) $container->getParameter('kernel.app_name')
            : 'Console';

        $version = $container->hasParameter('kernel.app_version')
            ? (string) $container->getParameter('kernel.app_version')
            : 'UNKNOWN';

        $application = new SymfonyConsoleApplication($name, $version);
        $application->setAutoExit(false);

        return $application;
    }
}
