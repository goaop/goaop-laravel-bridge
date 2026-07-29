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
 * Regression aspect for goaop/goaop-laravel-bridge#8: intercepts create()
 * on every *Repository class, exactly like the pointcut from the report.
 */
class AuditAspect implements Aspect
{
    /** @var list<string> */
    public static array $intercepted = [];

    #[Around('execution(public Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving\*Repository->create(*))')]
    public function auditCreate(MethodInvocation $invocation): mixed
    {
        self::$intercepted[] = 'around:' . $invocation->getMethod()->getName();

        $result = $invocation->proceed();
        if (is_array($result)) {
            $result['audited'] = true;
        }

        return $result;
    }
}
