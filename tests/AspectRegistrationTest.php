<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests;

use Go\Core\AspectContainer;
use Go\Laravel\GoAopBridge\Tests\Fixtures\EmptyAspect;

class AspectRegistrationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app->singleton(EmptyAspect::class);
        $app->tag([EmptyAspect::class], 'goaop.aspect');
    }

    public function testTaggedAspectsAreRegisteredInAspectContainer(): void
    {
        $aspectContainer = $this->app->make(AspectContainer::class);

        $this->assertTrue($aspectContainer->has(EmptyAspect::class));
        $this->assertSame(
            $this->app->make(EmptyAspect::class),
            $aspectContainer->getService(EmptyAspect::class)
        );
    }
}
