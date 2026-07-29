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

class ConfigAspectRegistrationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('go_aop.aspects', [EmptyAspect::class]);
    }

    public function testConfigDeclaredAspectsAreRegisteredInAspectContainer(): void
    {
        $aspectContainer = $this->app->make(AspectContainer::class);

        $this->assertTrue($aspectContainer->has(EmptyAspect::class));
    }
}
