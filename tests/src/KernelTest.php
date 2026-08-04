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

use Derafu\Console\DependencyInjection\CommandCompilerPass;
use Derafu\Console\Kernel;
use Derafu\Kernel\Contract\EnvironmentInterface;
use Derafu\Kernel\Environment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

// ConsoleKernelTrait is exercised through Kernel (its only current
// consumer in this package) since PHPUnit does not support targeting a
// trait directly with #[CoversClass]. CommandCompilerPass is already
// covered by its own dedicated test; here it is only used indirectly as
// part of building a real, compiled container.
#[CoversClass(Kernel::class)]
#[UsesClass(CommandCompilerPass::class)]
final class KernelTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/derafu-console-tests-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/*') ?: [];
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->cacheDir);
        }
    }

    private function createKernel(string $fixture = 'kernel'): Kernel
    {
        $projectDir = __DIR__ . '/../fixtures/' . $fixture;

        $environment = new Environment(
            name: EnvironmentInterface::TEST,
            debug: true,
            directories: [
                'project' => $projectDir,
                'config' => $projectDir . '/config',
                'cache' => $this->cacheDir,
            ],
        );

        // A unique kernel ID is required so each test gets its own cached
        // container class name. Otherwise, two tests booting a Kernel of
        // the same class within the same PHP process would both try to
        // declare a class named after md5(static::class), causing a
        // "Cannot redeclare class" fatal error.
        return new Kernel($environment, kernelId: md5(uniqid('', true)));
    }

    #[Test]
    public function discoversAndRunsATaggedCommandFromTheContainer(): void
    {
        $kernel = $this->createKernel();

        $input = new ArrayInput(['command' => 'app:greet']);
        $output = new BufferedOutput();

        $exitCode = $kernel->run($input, $output);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Hello, Derafu Console!', $output->fetch());
    }

    #[Test]
    public function listsTheDiscoveredCommandInTheApplication(): void
    {
        $kernel = $this->createKernel();

        $input = new ArrayInput(['command' => 'list', '--raw' => true]);
        $output = new BufferedOutput();

        $kernel->run($input, $output);

        $this->assertStringContainsString('app:greet', $output->fetch());
    }

    #[Test]
    public function usesTheApplicationNameAndVersionDefinedAsContainerParameters(): void
    {
        $kernel = $this->createKernel();

        $input = new ArrayInput(['--version' => true]);
        $output = new BufferedOutput();

        $kernel->run($input, $output);

        $this->assertStringContainsString(
            'Derafu Console Fixture 9.9.9',
            $output->fetch()
        );
    }

    #[Test]
    public function fallsBackToDefaultNameAndVersionWhenNotConfigured(): void
    {
        $kernel = $this->createKernel('kernel-defaults');

        $input = new ArrayInput(['--version' => true]);
        $output = new BufferedOutput();

        $kernel->run($input, $output);

        // Symfony Console's own getLongVersion() omits the version string
        // entirely when it is exactly 'UNKNOWN', so only the default name
        // ('Console') is expected here.
        $this->assertSame('Console' . \PHP_EOL, $output->fetch());
    }
}
