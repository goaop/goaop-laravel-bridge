<?php

namespace Go\Laravel\GoAopBridge\Tests\Integration;

use Go\Core\AspectContainer;
use Go\Core\AspectKernel;
use Go\Laravel\GoAopBridge\GoAopServiceProvider;
use Go\Laravel\GoAopBridge\Tests\TestCase;

final class GoAopServiceProviderTest extends TestCase
{

    public function testBoot()
    {
        $app = $this->createApplication();

        $sut = new GoAopServiceProvider($app);
        $sut->register();

        $sut->boot();

        $this->assertInstanceOf(GoAopServiceProvider::class, $sut);
    }

    public function testRegister()
    {
        $app = $this->createApplication();

        $sut = new GoAopServiceProvider($app);

        $sut->register();

        $this->assertInstanceOf(GoAopServiceProvider::class, $sut);
    }

    public function testProvides()
    {
        $app = $this->createApplication();

        $sut = new GoAopServiceProvider($app);

        $expected = [AspectKernel::class, AspectContainer::class];
        $actual = $sut->provides();

        $this->assertEquals($expected, $actual);
        $this->assertInstanceOf(GoAopServiceProvider::class, $sut);
    }
}
