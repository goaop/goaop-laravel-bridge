<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests\Fixtures\Unweavable;

/**
 * Methods returning by reference cannot be woven: the engine rejects them,
 * so the cache warmup must report this file as a failure.
 */
class ReferenceService
{
    private int $counter = 0;

    public function &counter(): int
    {
        return $this->counter;
    }
}
