<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests;

use Go\Aop\Aspect;
use Go\Laravel\GoAopBridge\GoAopServiceProvider;
use Go\Laravel\GoAopBridge\Tests\Fixtures\EmptyAspect;
use InvalidArgumentException;
use stdClass;

class AspectConfigValidationTest extends TestCase
{
    public function testAspectsConfigurationMustBeAList(): void
    {
        $this->app['config']->set('go_aop.aspects', EmptyAspect::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The "go_aop.aspects" configuration must be a list of aspect class names, "string" given.'
        );

        $this->bootProvider();
    }

    public function testAspectsConfigurationMustContainClassNames(): void
    {
        $this->app['config']->set('go_aop.aspects', [42]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The "go_aop.aspects" configuration must contain class names, "int" given.'
        );

        $this->bootProvider();
    }

    public function testConfiguredAspectMustImplementTheAspectInterface(): void
    {
        $this->app['config']->set('go_aop.aspects', [stdClass::class]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Aspect "%s" must implement the "%s" interface.',
            stdClass::class,
            Aspect::class
        ));

        $this->bootProvider();
    }

    /**
     * Boots a fresh provider instance against the current configuration,
     * as the package provider itself is booted before the test body runs.
     */
    private function bootProvider(): void
    {
        (new GoAopServiceProvider($this->app))->boot();
    }
}
