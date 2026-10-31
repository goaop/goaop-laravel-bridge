<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests\Fixtures;

use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Attribute\Before;

/**
 * Matches a method returning by reference, which the engine refuses to weave.
 */
class ReferenceAspect implements Aspect
{
    #[Before('execution(public Go\Laravel\GoAopBridge\Tests\Fixtures\Unweavable\ReferenceService->counter(*))')]
    public function beforeCounter(MethodInvocation $invocation): void
    {
    }
}
