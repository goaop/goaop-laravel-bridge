<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

/*
 * Warmup worker of the parallel warmup tests.
 *
 * The real worker is the application's "artisan aop:warmup-worker"; under
 * testbench there is no artisan booting the test application, so this script
 * boots a testbench application with the configuration of the weaving tests
 * (arguments: the cache directory, the number of cached files and optionally
 * the directory to build the cache in) and runs the same worker loop on it.
 */

use Go\Instrument\ClassLoading\Warmup\WarmupWorker;
use Go\Laravel\GoAopBridge\GoAopServiceProvider;
use Go\Laravel\GoAopBridge\Tests\Fixtures\LoggingAspect;
use Illuminate\Foundation\Application as LaravelApplication;
use Orchestra\Testbench\Foundation\Application;

require __DIR__ . '/../../../vendor/autoload.php';

[, $cacheDir, $cachedFiles] = $argv;
$buildDir = $argv[3] ?? null;

Application::create(
    resolvingCallback: static function (LaravelApplication $app) use ($cacheDir): void {
        // Runs before the kernel is initialized at the start of the boot phase
        $app->booting(static function () use ($app, $cacheDir): void {
            $app['config']->set('go_aop.debug', true);
            $app['config']->set('go_aop.cacheDir', $cacheDir);
            // The same application root as WeavingTestCase: the cache records are keyed relative to it
            $app['config']->set('go_aop.appDir', dirname(__DIR__, 3));
            $app['config']->set('go_aop.includePaths', [dirname(__DIR__) . '/Weaving']);
            $app['config']->set('go_aop.excludePaths', []);
            $app['config']->set('go_aop.aspects', [LoggingAspect::class]);
        });
    },
    options: [
        'extra' => ['providers' => [GoAopServiceProvider::class]],
        'load_environment_variables' => false,
    ],
);

// Lets the tests tell that the files were woven by a worker process
touch($cacheDir . '/worker-' . getmypid());

exit(WarmupWorker::main(__FILE__, (int) $cachedFiles, null, null, $buildDir));
