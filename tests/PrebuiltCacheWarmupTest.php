<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests;

use Go\Aop\Aspect;
use stdClass;

/**
 * With Features::PREBUILT_CACHE a request fails on a class below the include
 * paths that the cache misses. "aop:warmup" and its workers boot the
 * application, and with it the kernel, before the command runs: a class the
 * application loads while it boots must be served there, not failed, or the
 * cache could never be built (goaop/framework#808).
 */
class PrebuiltCacheWarmupTest extends ConsoleProcessTestCase
{
    public function testRequestFailsOnAClassTheCacheMisses(): void
    {
        [$exitCode, $output] = $this->console([], 'about');

        $this->assertNotSame(0, $exitCode, $output);
        $this->assertStringContainsString('missing from the prebuilt AOP cache', $output);
    }

    public function testWarmupBuildsTheCacheOfTheApplication(): void
    {
        [$exitCode, $output] = $this->console([], 'aop:warmup');

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('Go! AOP proxy cache has been warmed up.', $output);
        $this->assertNotEmpty(glob($this->cacheDir . '/tests/Fixtures/Weaving/GreetingService*'));
        $this->assertApplicationBootsFromTheCache();
    }

    public function testWarmupWorkersBuildTheCacheOfTheApplication(): void
    {
        [$exitCode, $output] = $this->console([], 'aop:warmup', '--workers=2');

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringNotContainsString('Worker processes cannot be started', $output);
        $this->assertNotEmpty(glob($this->cacheDir . '/tests/Fixtures/Weaving/GreetingService*'));
        $this->assertApplicationBootsFromTheCache();
    }

    public function testOptionsBeforeTheCommandNameAreSkipped(): void
    {
        [$exitCode, $output] = $this->console([], '-v', 'aop:warmup');

        $this->assertSame(0, $exitCode, $output);
        $this->assertApplicationBootsFromTheCache();
    }

    /**
     * The configured aspects are not loaded when the kernel starts: a command
     * that loads no class the cache misses runs before the cache is built, as
     * "composer install" (package:discover) and "optimize" do on a deployment
     */
    public function testCommandsRunBeforeTheCacheIsBuilt(): void
    {
        $this->environment['GOAOP_TEST_BOOT_TIME_WEAVING'] = '0';
        try {
            foreach (['package:discover', 'optimize'] as $command) {
                [$exitCode, $output] = $this->console([], $command);
                $this->assertSame(0, $exitCode, $command . ': ' . $output);
            }

            [$exitCode, $output] = $this->console([], 'test:greet');
            $this->assertNotSame(0, $exitCode, 'The woven class is missing from the cache: ' . $output);
            $this->assertStringContainsString('missing from the prebuilt AOP cache', $output);

            [$exitCode, $output] = $this->console([], 'aop:warmup');
            $this->assertSame(0, $exitCode, $output);

            [$exitCode, $output] = $this->console([], 'test:greet');
            $this->assertSame(0, $exitCode, $output);
            $this->assertStringContainsString('Hello, Laravel! Please, come in.', $output, 'The aspect below the include paths is applied');
        } finally {
            // "optimize" caches the configuration of the shared testbench skeleton
            $this->console([], 'optimize:clear');
        }
    }

    /**
     * An entry of the aspects config that is not an aspect is not checked when
     * the kernel starts, but fails the build of the cache
     */
    public function testWarmupFailsOnAConfiguredClassThatIsNotAnAspect(): void
    {
        $this->environment['GOAOP_TEST_BOOT_TIME_WEAVING'] = '0';
        $this->environment['GOAOP_TEST_EXTRA_ASPECT']      = stdClass::class;

        [$exitCode, $output] = $this->console([], 'package:discover');
        $this->assertSame(0, $exitCode, $output);

        [$exitCode, $output] = $this->console([], 'aop:warmup');
        $this->assertNotSame(0, $exitCode, $output);
        $this->assertStringContainsString(sprintf('Aspect "%s" must implement the "%s" interface.', stdClass::class, Aspect::class), $output);
    }

    /**
     * Once built, the cache serves the application, the class created while it boots included
     */
    private function assertApplicationBootsFromTheCache(): void
    {
        [$exitCode, $output] = $this->console([], 'about');

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString($this->cacheDir, $output);
    }
}
