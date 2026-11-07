<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests;

use Go\Core\AspectKernel;
use Go\Laravel\GoAopBridge\Console\WarmupCommand;
use Go\Laravel\GoAopBridge\Tests\Fixtures\TestWorkerWarmupCommand;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * "aop:warmup --workers=N" weaves in worker processes and merges their
 * cache records; "--cache-dir" builds the cache in another directory.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class ParallelWarmupCommandTest extends WeavingTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('go_aop.parsedFilesLimit', 7);
        $app->bind(WarmupCommand::class, TestWorkerWarmupCommand::class);
    }

    public function testFilesAreWovenInWorkerProcesses(): void
    {
        $this->artisan('aop:warmup', ['--workers' => '2', '--cached-files' => '5'])->assertSuccessful();

        $this->assertNotEmpty(TestWorkerWarmupCommand::$commandLines, 'A worker command line must have been requested');
        $this->assertSame('5', TestWorkerWarmupCommand::$commandLines[0][3]);
        $this->assertNotEmpty(glob(static::aopCacheDir() . '/worker-*'), 'A worker process must have been started');
        $this->assertTrue($this->cacheContainsFileFor('GreetingService'));
        $this->assertFileExists(static::aopCacheDir() . '/_include.cache', 'The coordinator writes the cache metadata');
        $includeMap = (string) file_get_contents(static::aopCacheDir() . '/_include.cache');
        $this->assertStringContainsString('GreetingService.php', $includeMap);
        $this->assertStringContainsString('ReportGenerator.php', $includeMap, 'Files without matching advice are recorded as well');
    }

    public function testCachedFilesDefaultToTheParsedFilesLimitOption(): void
    {
        $this->assertSame(7, $this->app->make(AspectKernel::class)->getOptions()['parsedFilesLimit']);

        $this->artisan('aop:warmup', ['--workers' => '2'])->assertSuccessful();

        $this->assertSame('7', TestWorkerWarmupCommand::$commandLines[0][3]);
    }

    public function testCacheIsBuiltInAnotherDirectory(): void
    {
        $buildDir = static::aopCacheDir() . '-build';

        $this->artisan('aop:warmup', ['--cache-dir' => $buildDir])->assertSuccessful();

        $this->assertFileExists($buildDir . '/_include.cache');
        $this->assertNotEmpty(glob($buildDir . '/tests/Fixtures/Weaving/GreetingService*'));
        $this->assertFalse($this->cacheContainsFileFor('GreetingService'), 'The directory of the kernel stays untouched');
    }

    public function testWorkersBuildTheCacheInAnotherDirectory(): void
    {
        $buildDir = static::aopCacheDir() . '-build';

        $this->artisan('aop:warmup', ['--workers' => '2', '--cache-dir' => $buildDir])->assertSuccessful();

        $this->assertSame($buildDir, TestWorkerWarmupCommand::$commandLines[0][4]);
        $this->assertFileExists($buildDir . '/_include.cache');
        $this->assertNotEmpty(glob($buildDir . '/tests/Fixtures/Weaving/GreetingService*'));
    }

    public function testWorkersOptionMustBeAPositiveInteger(): void
    {
        $this->expectExceptionMessage('Option --workers must be a positive integer, got "0"');

        $this->artisan('aop:warmup', ['--workers' => '0']);
    }
}
