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

/**
 * Runtime environment for handling console (CLI) applications.
 *
 * Mirrors `Derafu\Http\Runtime`'s role for HTTP applications: resolving the
 * real process context *before* any Kernel exists. A Kernel's own
 * `Derafu\Kernel\Environment::getEnv()` cannot help here — it is an
 * instance method, and the instance does not exist yet until `$environment`
 * and `$debug` (what this class resolves) have already been decided.
 *
 * `$_ENV` alone is not reliable for this: it is only populated when the
 * `variables_order` php.ini directive includes `E`, which is not every
 * PHP installation's default (e.g. a stock Homebrew PHP on macOS ships
 * with `GPCS`, without `E`). `$_SERVER` does carry real process
 * environment variables in the CLI SAPI regardless of that setting, so
 * `getApplicationContext()` merges both, the same way
 * `Derafu\Http\Runtime::getApplicationContext()` already does.
 *
 * Defaults to `prod`/`false` when nothing is set — deliberately the
 * opposite of `Derafu\Http\Runtime`'s `dev`/`true`. An HTTP app's `dev`/
 * `true` fallback only matters when it has no `.env` of its own, which is
 * not the expected case (`Environment::loadEnvironmentVariables()` still
 * loads one). A console tool installed via Composer and run as
 * `bin/console` has no such file: the values this class resolves *are*
 * the actual default a fresh install runs with, so it must be the safe
 * one — a stack trace should never be exposed unless explicitly asked
 * for via `APP_DEBUG`.
 */
final class Runtime
{
    /**
     * Gets the application context from the real process environment.
     *
     * @return array<string, mixed> The application context, with at least
     * `APP_ENV` and `APP_DEBUG` set.
     */
    public static function getApplicationContext(): array
    {
        // Merge server and environment variables.
        $context = array_merge($_SERVER, $_ENV);

        // Application environment settings.
        $context['APP_ENV'] = $context['APP_ENV'] ?? 'prod';
        $context['APP_DEBUG'] = $context['APP_DEBUG'] ?? false;

        return $context;
    }

    /**
     * Resolves the application context, builds the Kernel through the
     * given factory, and runs it.
     *
     * @param callable(array<string, mixed>): object $app Factory that
     * receives the resolved context and returns a Kernel exposing
     * `run(): int` (e.g. one using `ConsoleKernelTrait`).
     * @return int The exit code returned by the Kernel's `run()`.
     */
    public static function run(callable $app): int
    {
        $kernel = $app(static::getApplicationContext());

        return $kernel->run();
    }
}
