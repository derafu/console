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

use Derafu\Kernel\MicroKernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Ready-to-use console Kernel.
 *
 * This class is a convenience for projects that only need to run console
 * commands and do not need to reuse or compose with another existing
 * Kernel (e.g. an HTTP one). It reads its services from `services.yaml` in
 * the environment's configuration directory.
 *
 * Usage in bin/console:
 *
 *   use Derafu\Console\Kernel;
 *   use Derafu\Console\Runtime;
 *
 *   exit(Runtime::run(fn (array $context): Kernel => new Kernel(
 *       $context['APP_ENV'],
 *       (bool) $context['APP_DEBUG'],
 *   )));
 *
 * `Runtime::run()` is not optional convenience here: it resolves
 * `APP_ENV`/`APP_DEBUG` from `$_SERVER` and `$_ENV` together, which reading
 * `$_ENV` alone (e.g. `$_ENV['APP_ENV'] ?? 'dev'`) cannot do reliably —
 * `$_ENV` is only populated when the `variables_order` php.ini directive
 * includes `E`, which is not every PHP installation's default. See
 * `Runtime` for details.
 *
 * Projects that already have their own Kernel (HTTP-based or otherwise)
 * should not extend this class. Instead, they should use
 * ConsoleKernelTrait directly on their own Kernel, or on a dedicated
 * subclass of it. See ConsoleKernelTrait for details.
 */
class Kernel extends MicroKernel
{
    use ConsoleKernelTrait;

    /**
     * {@inheritDoc}
     */
    protected const CONFIG_FILES = [
        'services.yaml' => 'yaml',
    ];

    /**
     * {@inheritDoc}
     *
     * PhpFileLoader is required even though services.php is not part of
     * CONFIG_FILES: Derafu\Kernel\MicroKernel uses it internally to resolve
     * the Kernel's own class file when building the ContainerConfigurator.
     */
    protected const CONFIG_LOADERS = [
        PhpFileLoader::class,
        YamlFileLoader::class,
    ];

    /**
     * {@inheritDoc}
     */
    protected function configure(
        ContainerConfigurator $configurator,
        ContainerBuilder $container
    ): void {
        $this->configureConsole($container);
    }
}
