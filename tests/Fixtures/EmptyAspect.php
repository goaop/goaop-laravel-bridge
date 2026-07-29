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

/**
 * Advice-free aspect used to verify the registration plumbing only.
 */
class EmptyAspect implements Aspect
{
}
