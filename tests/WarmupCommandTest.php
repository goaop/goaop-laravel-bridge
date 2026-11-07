<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class WarmupCommandTest extends WeavingTestCase
{
    public function testWarmupCommandGeneratesProxyCache(): void
    {
        // The cache directory is named after the process id, which may be reused
        @unlink(static::aopCacheDir() . '/_preload.php');

        $this->artisan('aop:warmup')->assertSuccessful();

        $this->assertTrue(
            $this->cacheContainsFileFor('GreetingService'),
            'Expected aop:warmup to generate a cache entry for GreetingService'
        );
        $this->assertFileDoesNotExist(static::aopCacheDir() . '/_preload.php', 'No preload script without --preload');
    }

    public function testWarmupCommandWritesThePreloadScriptToTheCacheDirectory(): void
    {
        $this->artisan('aop:warmup', ['--preload' => null])
            ->expectsOutputToContain('Preload script written to')
            ->assertSuccessful();

        $preloadScript = static::aopCacheDir() . '/_preload.php';
        $this->assertFileExists($preloadScript);
        $this->assertStringContainsString('GreetingService', (string) file_get_contents($preloadScript));
        $this->assertPreloadScriptRuns($preloadScript);
    }

    public function testWarmupCommandWritesThePreloadScriptToTheGivenFile(): void
    {
        $preloadScript = static::aopCacheDir() . '-preload.php';
        @unlink($preloadScript);
        @unlink(static::aopCacheDir() . '/_preload.php');

        $this->artisan('aop:warmup', ['--preload' => $preloadScript])->assertSuccessful();

        $this->assertFileExists($preloadScript);
        $this->assertFileDoesNotExist(static::aopCacheDir() . '/_preload.php');
        $this->assertPreloadScriptRuns($preloadScript);
    }

    /**
     * Runs the script the way opcache.preload does, in a PHP process of its own
     */
    private function assertPreloadScriptRuns(string $preloadScript): void
    {
        $command = sprintf('%s -d display_errors=stderr %s 2>&1', escapeshellarg(PHP_BINARY), escapeshellarg($preloadScript));
        exec($command, $output, $exitCode);

        $this->assertSame(0, $exitCode, implode(PHP_EOL, $output));
    }
}
