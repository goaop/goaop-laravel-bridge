<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests;

use Go\Laravel\GoAopBridge\Tests\Fixtures\LoggingAspect;

/**
 * Base class for tests exercising real weaving. Subclasses must run in
 * separate PHPUnit processes: the kernel only honours its first init(),
 * and these tests need their own appDir/includePaths configuration.
 */
abstract class WeavingTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('go_aop.appDir', dirname(__DIR__));
        $app['config']->set('go_aop.includePaths', [__DIR__ . '/Fixtures/Weaving']);
        $app['config']->set('go_aop.aspects', [LoggingAspect::class]);
    }

    /**
     * Recursively looks for a generated cache entry mentioning the class name.
     */
    protected function cacheContainsFileFor(string $shortClassName): bool
    {
        $cacheDir = static::aopCacheDir();
        if (!is_dir($cacheDir)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cacheDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (str_contains($file->getFilename(), $shortClassName)) {
                return true;
            }
        }

        return false;
    }
}
