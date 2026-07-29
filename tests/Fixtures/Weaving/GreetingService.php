<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving;

use RuntimeException;

/**
 * Weaving target. This class must only ever be loaded after the aspect
 * kernel has been initialized, otherwise no proxy is generated for it.
 */
class GreetingService
{
    public function greet(string $name): string
    {
        return sprintf('Hello, %s!', $name);
    }

    public function shout(string $phrase): string
    {
        return $phrase . '!';
    }

    public function explode(): never
    {
        throw new RuntimeException('boom');
    }
}
