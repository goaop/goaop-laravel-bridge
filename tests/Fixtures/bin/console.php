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
 * variable, which the worker processes inherit. The application creates a
 * service below the include paths while it boots (BootTimeWeavingProvider).
 */

use Go\Aop\Features;
use Go\Laravel\GoAopBridge\Console\WarmupCommand;
use Go\Laravel\GoAopBridge\GoAopServiceProvider;
use Go\Laravel\GoAopBridge\Tests\Fixtures\BootTimeWeavingProvider;
use Go\Laravel\GoAopBridge\Tests\Fixtures\ConsoleWarmupCommand;
use Go\Laravel\GoAopBridge\Tests\Fixtures\LoggingAspect;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application as LaravelApplication;
use Orchestra\Testbench\Foundation\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

require __DIR__ . '/../../../vendor/autoload.php';

$cacheDir = (string) getenv('GOAOP_TEST_CACHE_DIR');

// artisan boots the application inside the console kernel, which reports a
// failure of the boot with exit code 1; testbench boots it right away
try {
    $app = Application::create(
        resolvingCallback: static function (LaravelApplication $app) use ($cacheDir): void {
            // Runs before the kernel is initialized at the start of the boot phase
            $app->booting(static function () use ($app, $cacheDir): void {
                $app['config']->set('go_aop.debug', false);
                $app['config']->set('go_aop.features', Features::PREBUILT_CACHE);
                $app['config']->set('go_aop.cacheDir', $cacheDir);
                $app['config']->set('go_aop.appDir', dirname(__DIR__, 3));
                $app['config']->set('go_aop.includePaths', [dirname(__DIR__) . '/Weaving']);
                $app['config']->set('go_aop.excludePaths', []);
                $app['config']->set('go_aop.aspects', [LoggingAspect::class]);
            });
            $app->bind(WarmupCommand::class, ConsoleWarmupCommand::class);
        },
        options: [
            'extra' => ['providers' => [GoAopServiceProvider::class, BootTimeWeavingProvider::class]],
            'load_environment_variables' => false,
        ],
    );
} catch (Throwable $e) {
    fwrite(STDERR, get_debug_type($e) . ': ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

exit($app->make(Kernel::class)->handle(new ArgvInput(), new ConsoleOutput()));
