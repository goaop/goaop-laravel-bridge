<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests\Fixtures;

/**
 * Default implementation mixed into classes matched by the
 * DeclareParents introduction advice.
 */
trait StringableImpl
{
    public function __toString(): string
    {
        return 'introduced:' . static::class;
    }
}
