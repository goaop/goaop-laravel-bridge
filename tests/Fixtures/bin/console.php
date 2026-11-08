<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

/*
 * Console of an application with a prebuilt AOP cache, the "artisan" of the
 * build process tests: "php console.php aop:warmup" boots the application,
 * and with it the AOP kernel, before the command runs, like artisan does.
 *
 * The cache directory is taken from the GOAOP_TEST_CACHE_DIR environment
 * variable, which the worker processes inherit. One of the aspects lies below
 * the include paths (tests/Fixtures/App), as in the "app/" directory of an
 * application. The application creates a service below the include paths
 * while it boots (BootTimeWeavingProvider), unless GOAOP_TEST_BOOT_TIME_WEAVING
 * is "0". GOAOP_TEST_EXTRA_ASPECT adds a class to the configured aspects. The
 * "test:greet" command calls the woven service.
 */

use Go\Aop\Features;
use Go\Laravel\GoAopBridge\Console\WarmupCommand;
use Go\Laravel\GoAopBridge\GoAopServiceProvider;
use Go\Laravel\GoAopBridge\Tests\Fixtures\App\PoliteAspect;
use Go\Laravel\GoAopBridge\Tests\Fixtures\BootTimeWeavingProvider;
use Go\Laravel\GoAopBridge\Tests\Fixtures\ConsoleWarmupCommand;
use Go\Laravel\GoAopBridge\Tests\Fixtures\LoggingAspect;
use Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving\GreetingService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application as LaravelApplication;
use Orchestra\Testbench\Foundation\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

require __DIR__ . '/../../../vendor/autoload.php';

$cacheDir  = (string) getenv('GOAOP_TEST_CACHE_DIR');
$aspects   = [LoggingAspect::class, PoliteAspect::class];
if (getenv('GOAOP_TEST_EXTRA_ASPECT') !== false) {
    $aspects[] = getenv('GOAOP_TEST_EXTRA_ASPECT');
}
$providers = [GoAopServiceProvider::class];
if (getenv('GOAOP_TEST_BOOT_TIME_WEAVING') !== '0') {
    $providers[] = BootTimeWeavingProvider::class;
}

// artisan boots the application inside the console kernel, which reports a
// failure of the boot with exit code 1; testbench boots it right away
try {
    $app = Application::create(
        resolvingCallback: static function (LaravelApplication $app) use ($cacheDir, $aspects): void {
            // Runs before the kernel is initialized at the start of the boot phase
            $app->booting(static function () use ($app, $cacheDir, $aspects): void {
                $app['config']->set('go_aop.debug', false);
                $app['config']->set('go_aop.features', Features::PREBUILT_CACHE);
                $app['config']->set('go_aop.cacheDir', $cacheDir);
                $app['config']->set('go_aop.appDir', dirname(__DIR__, 3));
                $app['config']->set('go_aop.includePaths', [dirname(__DIR__) . '/Weaving', dirname(__DIR__) . '/App']);
                $app['config']->set('go_aop.excludePaths', []);
                $app['config']->set('go_aop.aspects', $aspects);
            });
            $app->bind(WarmupCommand::class, ConsoleWarmupCommand::class);
        },
        options: [
            'extra' => ['providers' => $providers],
            'load_environment_variables' => false,
        ],
    );
} catch (Throwable $e) {
    fwrite(STDERR, get_debug_type($e) . ': ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$kernel = $app->make(Kernel::class);
$kernel->command('test:greet', function (): void {
    $this->line(new GreetingService()->greet('Laravel'));
});

// The console kernel of testbench rethrows the exception of a command, the
// one of artisan reports it with exit code 1
try {
    $exitCode = $kernel->handle(new ArgvInput(), new ConsoleOutput());
} catch (Throwable $e) {
    fwrite(STDERR, get_debug_type($e) . ': ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

exit($exitCode);
