<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests;

use Go\Core\AspectContainer;
use Go\Laravel\GoAopBridge\GoAopServiceProvider;
use Go\Laravel\GoAopBridge\Tests\Fixtures\BootTimeBindingProvider;
use Go\Laravel\GoAopBridge\Tests\Fixtures\DependentAspect;
use Go\Laravel\GoAopBridge\Tests\Fixtures\Salutation;
use Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving\GreetingService;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Config-declared aspects are deferred container services: weaving reads
 * their classes only, and the aspect is resolved through the Laravel
 * container on the first advice call. An aspect may therefore depend on
 * services that are bound after the kernel started (in a boot() method).
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class LazyAspectCreationTest extends WeavingTestCase
{
    protected function getPackageProviders($app): array
    {
        return [GoAopServiceProvider::class, BootTimeBindingProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('go_aop.aspects', [DependentAspect::class]);
    }

    public function testAspectIsNotCreatedWhileBooting(): void
    {
        $this->assertSame(0, DependentAspect::$instances, 'The aspect must not be created before its first advice call');
        $this->assertTrue($this->app->make(AspectContainer::class)->has(DependentAspect::class));
    }

    public function testAspectIsCreatedWithBootTimeDependencyOnFirstAdviceCall(): void
    {
        $service = new GreetingService();

        $this->assertSame('Welcome, Hello, Laravel!', $service->greet('Laravel'));
        $this->assertSame(1, DependentAspect::$instances);
    }

    public function testLaravelContainerIsTheDelegateOfTheAspectContainer(): void
    {
        $aspectContainer = $this->app->make(AspectContainer::class);

        $this->assertTrue($aspectContainer->has(Salutation::class));
        $this->assertSame($this->app->make(Salutation::class), $aspectContainer->get(Salutation::class));
    }
}
