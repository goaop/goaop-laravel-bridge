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
use Go\Aop\Exception\InvalidConfigurationException;
use Go\Core\AspectContainer;
use Go\Core\AspectKernel;
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
     * Outside debug mode the configured classes are not loaded when the kernel
     * starts: a class that is not an aspect fails when it is resolved
     */
    public function testConfiguredAspectIsCheckedWhenResolvedOutsideDebugMode(): void
    {
        $this->app['config']->set('go_aop.debug', false);
        $this->app['config']->set('go_aop.aspects', [stdClass::class]);
        $this->bootProvider();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            'Aspect "%s" must implement the "%s" interface.',
            stdClass::class,
            Aspect::class
        ));

        $this->app->make(AspectContainer::class)->get(stdClass::class);
    }

    public function testConfiguredAspectClassesAreNotLoadedOutsideDebugMode(): void
    {
        $this->app['config']->set('go_aop.debug', false);
        $this->app['config']->set('go_aop.aspects', ['Go\\Laravel\\GoAopBridge\\Tests\\Fixtures\\MissingAspect']);

        $this->bootProvider();

        $this->assertTrue($this->app->make(AspectContainer::class)->has('Go\\Laravel\\GoAopBridge\\Tests\\Fixtures\\MissingAspect'));
    }

    public function testParsedFilesLimitMustBePositive(): void
    {
        // What a mistyped GOAOP_PARSED_FILES_LIMIT casts to
        $this->app['config']->set('go_aop.parsedFilesLimit', 0);

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Option "parsedFilesLimit" must be a positive integer, got 0.');

        $this->bootProvider();
    }

    /**
     * Re-registers the provider and resolves the kernel against the current
     * configuration, as the package provider itself has already configured
     * the kernel before the test body runs.
     */
    private function bootProvider(): void
    {
        (new GoAopServiceProvider($this->app))->register();
        $this->app->make(AspectKernel::class);
    }
}
