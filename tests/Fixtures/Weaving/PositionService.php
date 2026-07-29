<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests\Fixtures\Weaving;

/**
 * Plain collaborator injected into constructors of other woven classes.
 */
class PositionService
{
    public function defaultPosition(): string
    {
        return 'engineer';
    }
}
