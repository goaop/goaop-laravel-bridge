<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class WarmupCommandTest extends WeavingTestCase
{
    public function testWarmupCommandGeneratesProxyCache(): void
    {
        $this->artisan('aop:warmup')->assertSuccessful();

        $this->assertTrue(
            $this->cacheContainsFileFor('GreetingService'),
            'Expected aop:warmup to generate a cache entry for GreetingService'
        );
    }
}
