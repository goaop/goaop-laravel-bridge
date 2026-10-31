<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests\Fixtures;

use Illuminate\Support\ServiceProvider;

/**
 * Binds the dependency of the DependentAspect in boot(), i.e. after the
 * AOP kernel has been initialized at the start of the boot phase.
 */
class BootTimeBindingProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->instance(Salutation::class, new Salutation('Welcome,'));
    }
}
