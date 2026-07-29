<?php
/*
 * Go! AOP framework
 *
 * @copyright Copyright 2016, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Kernel;

use Go\Core\AspectContainer;
use Go\Core\AspectKernel;

/**
 * Laravel aspect kernel class
 *
 * Aspects are registered by the GoAopServiceProvider (from the
 * "go_aop.aspects" config list and the "goaop.aspect" container tag),
 * so no kernel-level configuration is required here.
 */
class AspectLaravelKernel extends AspectKernel
{
    protected function configureAop(AspectContainer $container): void
    {
    }
}
