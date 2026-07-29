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
use Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving\GreetingService;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use RuntimeException;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class AspectWeavingTest extends WeavingTestCase
{
    public function testBeforeAndAfterAdviceFireOnWovenMethod(): void
    {
        LoggingAspect::$log = [];

        $service = new GreetingService();

        $this->assertSame('Hello, Laravel!', $service->greet('Laravel'));
        $this->assertContains('before:greet', LoggingAspect::$log);
        $this->assertContains('after:greet', LoggingAspect::$log);
    }

    public function testAroundAdviceCanModifyReturnValue(): void
    {
        $service = new GreetingService();

        $this->assertSame('AOP!', $service->shout('aop'));
    }

    public function testAfterThrowingAdviceObservesExceptions(): void
    {
        LoggingAspect::$log = [];

        $service = new GreetingService();

        try {
            $service->explode();
            $this->fail('explode() should have thrown');
        } catch (RuntimeException $exception) {
            $this->assertSame('boom', $exception->getMessage());
        }

        $this->assertContains('afterThrowing:explode', LoggingAspect::$log);
    }

    public function testProxySourceIsGeneratedInCacheDirectory(): void
    {
        $service = new GreetingService();
        $service->greet('cache');

        $this->assertTrue(
            $this->cacheContainsFileFor('GreetingService'),
            'Expected a woven cache entry for GreetingService in ' . static::aopCacheDir()
        );
    }
}
