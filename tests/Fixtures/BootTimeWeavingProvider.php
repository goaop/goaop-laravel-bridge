<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests\Fixtures;

use Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving\GreetingService;
use Illuminate\Support\ServiceProvider;

/**
 * Creates a service below the include paths in boot(), i.e. after the AOP
 * kernel has been initialized: its class is loaded through the kernel while
 * the application boots, also in the process of "aop:warmup".
 */
class BootTimeWeavingProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->instance(GreetingService::class, new GreetingService());
    }
}
