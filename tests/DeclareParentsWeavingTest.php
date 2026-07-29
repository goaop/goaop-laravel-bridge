<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests;

use Go\Laravel\GoAopBridge\Tests\Fixtures\IntroductionAspect;
use Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving\ReportGenerator;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Stringable;

/**
 * Regression tests for goaop/goaop-laravel-bridge#21: inter-type
 * declarations (DeclareParents) must work under Laravel. The legacy
 * annotation grammar rejected the reported pointcut at parse time; the
 * attribute-based framework 4.x syntax replaces it entirely.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class DeclareParentsWeavingTest extends WeavingTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('go_aop.aspects', [IntroductionAspect::class]);
    }

    public function testIntroducedInterfaceIsImplementedByWovenClass(): void
    {
        $generator = new ReportGenerator();

        $this->assertInstanceOf(Stringable::class, $generator);
    }

    public function testIntroducedTraitProvidesDefaultImplementation(): void
    {
        $generator = new ReportGenerator();

        $this->assertSame('introduced:' . ReportGenerator::class, (string) $generator);
        $this->assertSame('Quarterly report', $generator->title(), 'Own methods must keep working');
    }

    public function testIntroductionProxyIsGeneratedInCache(): void
    {
        new ReportGenerator();

        $this->assertTrue(
            $this->cacheContainsFileFor('ReportGenerator'),
            'Expected a woven cache entry for ReportGenerator in ' . static::aopCacheDir()
        );
    }
}
