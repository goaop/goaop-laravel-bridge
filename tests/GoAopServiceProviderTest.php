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
use Go\Core\AspectKernel;
use Go\Laravel\GoAopBridge\Kernel\AspectLaravelKernel;

class GoAopServiceProviderTest extends TestCase
{
    public function testAspectKernelIsRegisteredAsSingleton(): void
    {
        $kernel = $this->app->make(AspectKernel::class);

        $this->assertInstanceOf(AspectLaravelKernel::class, $kernel);
        $this->assertSame($kernel, $this->app->make(AspectKernel::class));
    }

    public function testAspectContainerResolvesToKernelContainer(): void
    {
        $kernel = $this->app->make(AspectKernel::class);
        $aspectContainer = $this->app->make(AspectContainer::class);

        $this->assertSame($kernel->getContainer(), $aspectContainer);
    }

    public function testPackageConfigurationIsMerged(): void
    {
        $config = $this->app['config']->get('go_aop');

        $this->assertIsArray($config);
        $this->assertSame(static::aopCacheDir(), $config['cacheDir']);
        $this->assertArrayHasKey('appDir', $config);
        $this->assertArrayHasKey('includePaths', $config);
        $this->assertArrayHasKey('excludePaths', $config);
        $this->assertIsInt($config['features']);
        $this->assertIsInt($config['cacheFileMode']);
    }

    public function testKernelIsInitializedWithConfiguredOptions(): void
    {
        $kernel = $this->app->make(AspectKernel::class);
        $options = $kernel->getOptions();

        $this->assertSame(static::aopCacheDir(), $options['cacheDir']);
        $this->assertTrue($options['debug']);
    }
}
