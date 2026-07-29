<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests;

use Go\Laravel\GoAopBridge\Tests\Fixtures\AuditAspect;
use Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving\AuditService;
use Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving\PositionRepository;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Regression tests for goaop/goaop-laravel-bridge#8: on the legacy
 * framework 2.x stack, advice on "*Repository->create(*)" silently stopped
 * firing once the repository (or its collaborators) were constructor-injected
 * into another service. The woven pointcut must keep matching regardless of
 * how the target class gets instantiated.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class ConstructorInjectionWeavingTest extends WeavingTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('go_aop.aspects', [AuditAspect::class]);
    }

    public function testAdviceFiresOnContainerResolvedRepository(): void
    {
        AuditAspect::$intercepted = [];

        /** @var PositionRepository $repository */
        $repository = $this->app->make(PositionRepository::class);
        $created = $repository->create(['name' => 'Alice']);

        $this->assertContains('around:create', AuditAspect::$intercepted);
        $this->assertTrue($created['audited'] ?? false);
        $this->assertSame('engineer', $created['position']);
    }

    public function testAdviceFiresWhenRepositoryIsConstructorInjected(): void
    {
        AuditAspect::$intercepted = [];

        /** @var AuditService $service */
        $service = $this->app->make(AuditService::class);
        $recorded = $service->record(['name' => 'Bob']);

        $this->assertContains('around:create', AuditAspect::$intercepted);
        $this->assertTrue($recorded['audited'] ?? false);
        $this->assertSame('engineer', $recorded['recorded_for']);
    }

    public function testRepositoryProxyIsGeneratedInCache(): void
    {
        /** @var AuditService $service */
        $service = $this->app->make(AuditService::class);
        $service->record(['name' => 'Carol']);

        $this->assertTrue(
            $this->cacheContainsFileFor('PositionRepository'),
            'Expected a woven cache entry for PositionRepository in ' . static::aopCacheDir()
        );
    }
}
