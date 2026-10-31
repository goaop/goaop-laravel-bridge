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
 * Dependency of the DependentAspect, bound only once the application boots.
 */
final class Salutation
{
    public function __construct(public readonly string $text)
    {
    }
}
