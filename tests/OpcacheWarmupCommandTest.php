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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * "aop:warmup-opcache" primes the opcache file cache of the application,
 * after "aop:warmup", in a process started with the file cache settings.
 */
class OpcacheWarmupCommandTest extends ConsoleProcessTestCase
{
    private string $fileCacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileCacheDir = $this->cacheDir . '-opcache';
        $this->removeDirectory($this->fileCacheDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->fileCacheDir);

        parent::tearDown();
    }

    public function testCommandPrintsTheCommandLineWithoutTheFileCacheSettings(): void
    {
        $this->warmUpAopCache();

        [$exitCode, $output] = $this->console(['opcache.file_cache' => ''], 'aop:warmup-opcache');

        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString('Can not prime the opcache file cache', $output);
        $this->assertStringContainsString('console.php aop:warmup-opcache', $output);
    }

    public function testCommandPrimesTheFileCacheWithTheAopCacheAndTheApplication(): void
    {
        if (ini_get('opcache.enable_cli') === false) {
            $this->markTestSkipped('The opcache extension is not loaded');
        }
        $this->warmUpAopCache();
        mkdir($this->fileCacheDir, 0777, true);

        [$exitCode, $output] = $this->console(
            ['opcache.enable_cli' => '1', 'opcache.file_cache_only' => '1', 'opcache.file_cache' => $this->fileCacheDir],
            'aop:warmup-opcache',
            '--file-cache=' . $this->fileCacheDir,
            '--entry=' . __DIR__ . '/Fixtures/bin/console.php'
        );

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('Priming the opcache file cache', $output);
        $compiledFiles = $this->compiledFiles();
        $this->assertContains(realpath($this->cacheDir) . '/tests/Fixtures/Weaving/GreetingService.php', $compiledFiles, 'The woven class of the AOP cache');
        $this->assertContains(__DIR__ . '/Fixtures/bin/console.php', $compiledFiles, 'The entry point');
    }

    /**
     * Builds the AOP cache the application needs to boot, as a deployment does first
     */
    private function warmUpAopCache(): void
    {
        [$exitCode, $output] = $this->console([], 'aop:warmup');
        $this->assertSame(0, $exitCode, $output);
    }

    /**
     * Returns the source files the file cache holds a compiled script of
     *
     * @return list<string>
     */
    private function compiledFiles(): array
    {
        $files    = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->fileCacheDir, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $path = $file->getPathname();
            if (str_ends_with($path, '.bin')) {
                // <file cache>/<system id>/<absolute path of the source>.bin
                $relativePath = substr($path, strlen($this->fileCacheDir) + 1, -strlen('.bin'));
                $files[]      = substr($relativePath, (int) strpos($relativePath, '/'));
            }
        }

        return $files;
    }
}
