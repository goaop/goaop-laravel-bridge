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
use Go\Lang\Attribute\DeclareParents;
use Stringable;

/**
 * Inter-type declaration aspect: introduces the Stringable interface and
 * a default trait implementation into the matched class (issue #21).
 */
class IntroductionAspect implements Aspect
{
    #[DeclareParents(
        'within(Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving\ReportGenerator)',
        interfaceName: Stringable::class,
        traitName: StringableImpl::class,
    )]
    protected null $introduction;
}
