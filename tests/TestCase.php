<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests;

use Go\Laravel\GoAopBridge\GoAopServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * The AspectKernel is a process-global singleton and only honours the
     * options of its very first init() call, so every test in the process
     * must share the same cache directory.
     */
    public static function aopCacheDir(): string
    {
        return sys_get_temp_dir() . '/goaop-laravel-bridge-tests/' . getmypid();
    }

    protected function getPackageProviders($app): array
    {
        return [GoAopServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $cacheDir = static::aopCacheDir();
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        /** @var Application $app */
        $app['config']->set('go_aop.debug', true);
        $app['config']->set('go_aop.cacheDir', $cacheDir);
        $app['config']->set('go_aop.includePaths', [__DIR__ . '/Fixtures']);
        $app['config']->set('go_aop.excludePaths', []);
    }
}
