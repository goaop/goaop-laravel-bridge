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
use Go\Lang\Attribute\After;
use Go\Lang\Attribute\AfterThrowing;
use Go\Lang\Attribute\Around;
use Go\Lang\Attribute\Before;

/**
 * Attribute-based aspect used by the weaving tests.
 */
class LoggingAspect implements Aspect
{
    /** @var list<string> */
    public static array $log = [];

    #[Before('execution(public Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving\GreetingService->greet(*))')]
    public function logBeforeGreeting(MethodInvocation $invocation): void
    {
        self::$log[] = 'before:' . $invocation->getMethod()->getName();
    }

    #[After('execution(public Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving\GreetingService->greet(*))')]
    public function logAfterGreeting(MethodInvocation $invocation): void
    {
        self::$log[] = 'after:' . $invocation->getMethod()->getName();
    }

    #[Around('execution(public Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving\GreetingService->shout(*))')]
    public function shoutLouder(MethodInvocation $invocation): mixed
    {
        return strtoupper((string) $invocation->proceed());
    }

    #[AfterThrowing('execution(public Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving\GreetingService->explode(*))')]
    public function logException(MethodInvocation $invocation): void
    {
        self::$log[] = 'afterThrowing:' . $invocation->getMethod()->getName();
    }
}
