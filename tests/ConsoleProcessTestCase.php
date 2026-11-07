<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests;

use FilesystemIterator;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Base class of the tests running tests/Fixtures/bin/console.php, the console
 * of an application with a prebuilt AOP cache, in processes of their own, as
 * a deployment runs artisan. Every test starts with an empty cache directory.
 */
abstract class ConsoleProcessTestCase extends PHPUnitTestCase
{
    protected string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/goaop-laravel-bridge-tests/prebuilt-' . getmypid();
        $this->removeDirectory($this->cacheDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->cacheDir);
    }

    /**
     * Runs the console with the given arguments
     *
     * @param array<string, string> $iniSettings Settings of the PHP process
     *
     * @return array{int, string} Exit code and output (both streams)
     */
    protected function console(array $iniSettings, string ...$arguments): array
    {
        $command = [PHP_BINARY, '-d', 'display_errors=stderr'];
        foreach ($iniSettings as $name => $value) {
            array_push($command, '-d', $name . '=' . $value);
        }
        array_push($command, __DIR__ . '/Fixtures/bin/console.php', ...$arguments);
        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['redirect', 1]],
            $pipes,
            null,
            ['GOAOP_TEST_CACHE_DIR' => $this->cacheDir] + getenv()
        );
        $this->assertIsResource($process);
        $output = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        return [proc_close($process), $output];
    }

    protected function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($directory);
    }
}
