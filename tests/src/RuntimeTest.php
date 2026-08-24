<?php

declare(strict_types=1);

/**
 * Derafu: Console - Symfony Console integration for the Derafu Kernel.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsConsole;

use Derafu\Console\Runtime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Runtime::class)]
final class RuntimeTest extends TestCase
{
    #[Test]
    public function fallsBackToProdAndDebugFalseWhenNothingIsSet(): void
    {
        unset($_SERVER['APP_ENV'], $_SERVER['APP_DEBUG'], $_ENV['APP_ENV'], $_ENV['APP_DEBUG']);

        $context = Runtime::getApplicationContext();

        $this->assertSame('prod', $context['APP_ENV']);
        $this->assertFalse($context['APP_DEBUG']);
    }

    #[Test]
    public function readsRealValuesFromServerEvenWhenEnvIsEmpty(): void
    {
        // Regression: $_ENV alone is not reliable across PHP installs (it
        // depends on the `variables_order` php.ini directive including
        // `E`). This asserts $_SERVER is honored on its own, since that is
        // what a real process environment variable lands in under the CLI
        // SAPI regardless of that setting.
        unset($_ENV['APP_ENV'], $_ENV['APP_DEBUG']);
        $_SERVER['APP_ENV'] = 'prod';
        $_SERVER['APP_DEBUG'] = false;

        $context = Runtime::getApplicationContext();

        $this->assertSame('prod', $context['APP_ENV']);
        $this->assertFalse($context['APP_DEBUG']);

        unset($_SERVER['APP_ENV'], $_SERVER['APP_DEBUG']);
    }

    #[Test]
    public function envTakesPrecedenceOverServerWhenBothAreSet(): void
    {
        $_SERVER['APP_ENV'] = 'prod';
        $_ENV['APP_ENV'] = 'test';

        $context = Runtime::getApplicationContext();

        $this->assertSame('test', $context['APP_ENV']);

        unset($_SERVER['APP_ENV'], $_ENV['APP_ENV']);
    }

    #[Test]
    public function runBuildsTheKernelFromTheResolvedContextAndReturnsItsExitCode(): void
    {
        $_SERVER['APP_ENV'] = 'test';
        $_SERVER['APP_DEBUG'] = true;

        $receivedContext = null;
        $kernel = new class () {
            public function run(): int
            {
                return 7;
            }
        };

        $exitCode = Runtime::run(function (array $context) use ($kernel, &$receivedContext) {
            $receivedContext = $context;

            return $kernel;
        });

        $this->assertSame(7, $exitCode);
        $this->assertSame('test', $receivedContext['APP_ENV']);
        $this->assertTrue($receivedContext['APP_DEBUG']);

        unset($_SERVER['APP_ENV'], $_SERVER['APP_DEBUG']);
    }
}
