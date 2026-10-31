<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests;

use Go\Laravel\GoAopBridge\Tests\Fixtures\ReferenceAspect;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * The warmup must fail when a file cannot be woven (here: a method returning
 * by reference matched by a pointcut), so a deploy never ships an incomplete
 * cache. The errors are written to the error output of the command.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class WarmupCommandFailureTest extends WeavingTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('go_aop.includePaths', [__DIR__ . '/Fixtures/Unweavable']);
        $app['config']->set('go_aop.aspects', [ReferenceAspect::class]);
    }

    public function testWarmupCommandFailsWhenAFileCannotBeWoven(): void
    {
        $this->artisan('aop:warmup')
            ->expectsOutputToContain('[ERR]: ' . __DIR__ . '/Fixtures/Unweavable/ReferenceService.php')
            ->expectsOutputToContain('1 file(s) could not be woven')
            ->assertFailed();
    }

    public function testWarmupCommandStopsAtTheFirstFailureWithFailFast(): void
    {
        $this->artisan('aop:warmup', ['--fail-fast' => true])->assertFailed();
    }
}
