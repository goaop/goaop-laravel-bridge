<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests\Fixtures\App;

use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Attribute\Around;

/**
 * Aspect of tests/Fixtures/bin/console.php below the include paths, like an
 * aspect in "app/Aspects" of an application: with a prebuilt cache, loading
 * it before the cache is built fails the process.
 */
class PoliteAspect implements Aspect
{
    #[Around('execution(public Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving\GreetingService->greet(*))')]
    public function addPlease(MethodInvocation $invocation): string
    {
        return $invocation->proceed() . ' Please, come in.';
    }
}
