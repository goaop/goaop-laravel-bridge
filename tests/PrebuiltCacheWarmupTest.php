<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests;

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
     * Once built, the cache serves the application, the class created while it boots included
     */
    private function assertApplicationBootsFromTheCache(): void
    {
        [$exitCode, $output] = $this->console([], 'about');

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString($this->cacheDir, $output);
    }
}
