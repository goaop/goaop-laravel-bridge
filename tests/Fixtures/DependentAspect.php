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
use Go\Lang\Attribute\Around;

/**
 * Aspect with a constructor dependency that is only available after the
 * application has booted: it must be created on the first advice call,
 * never while the kernel weaves.
 */
class DependentAspect implements Aspect
{
    public static int $instances = 0;

    public function __construct(private readonly Salutation $salutation)
    {
        self::$instances++;
    }

    #[Around('execution(public Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving\GreetingService->greet(*))')]
    public function prependSalutation(MethodInvocation $invocation): mixed
    {
        $result = $invocation->proceed();

        return is_string($result) ? $this->salutation->text . ' ' . $result : $result;
    }
}
