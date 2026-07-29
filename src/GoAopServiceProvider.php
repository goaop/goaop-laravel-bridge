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

namespace Go\Laravel\GoAopBridge;

use Go\Aop\Aspect;
use Go\Core\AspectContainer;
use Go\Core\AspectKernel;
use Go\Laravel\GoAopBridge\Console\WarmupCommand;
use Go\Laravel\GoAopBridge\Kernel\AspectLaravelKernel;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

/**
 * Service provider for registration of Go! AOP framework
 */
class GoAopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), 'go_aop');

        $this->app->singleton(AspectKernel::class, function (): AspectKernel {
            $kernel = AspectLaravelKernel::getInstance();
            // @phpstan-ignore argument.type (options come from config and are validated by the kernel)
            $kernel->init($this->kernelOptions());

            return $kernel;
        });
        $this->app->singleton(
            AspectContainer::class,
            static fn ($app): AspectContainer => $app->make(AspectKernel::class)->getContainer()
        );

        // The kernel wraps the composer autoloader, and weaving only applies
        // to classes loaded after that point — so it must start at the very
        // beginning of the boot phase, before any provider boots.
        $this->app->booting(function (): void {
            $this->app->make(AspectKernel::class);
        });
    }

    public function boot(): void
    {
        $this->publishes([$this->configPath() => config_path('go_aop.php')], 'goaop-config');

        $this->registerAspects();

        if ($this->app->runningInConsole()) {
            $this->commands([WarmupCommand::class]);

            if (class_exists(AboutCommand::class)) {
                AboutCommand::add('Go! AOP', fn (): array => [
                    'Cache Directory' => (string) $this->app->make('config')->get('go_aop.cacheDir'),
                    'Debug Mode' => $this->app->make('config')->get('go_aop.debug') ? 'ENABLED' : 'OFF',
                ]);
            }
        }
    }

    /**
     * Registers aspects declared in the "go_aop.aspects" config list and
     * services tagged with "goaop.aspect" in the aspect container.
     */
    private function registerAspects(): void
    {
        /** @var AspectContainer $aspectContainer */
        $aspectContainer = $this->app->make(AspectContainer::class);
        $registered = [];

        $register = function (object $aspect) use ($aspectContainer, &$registered): void {
            if (!$aspect instanceof Aspect) {
                throw new InvalidArgumentException(sprintf(
                    'Aspect "%s" must implement the "%s" interface.',
                    $aspect::class,
                    Aspect::class
                ));
            }
            if (!isset($registered[$aspect::class])) {
                $aspectContainer->registerAspect($aspect);
                $registered[$aspect::class] = true;
            }
        };

        /** @var array<int, class-string> $configuredAspects */
        $configuredAspects = $this->app->make('config')->get('go_aop.aspects', []);
        foreach ($configuredAspects as $aspectClass) {
            $register($this->app->make($aspectClass));
        }

        foreach ($this->app->tagged('goaop.aspect') as $aspect) {
            $register($aspect);
        }
    }

    /**
     * Collects normalized kernel options from the merged configuration.
     *
     * @return array<string, mixed>
     */
    private function kernelOptions(): array
    {
        /** @var array<string, mixed> $config */
        $config = $this->app->make('config')->get('go_aop');

        // The "aspects" list is a bridge-level concept unknown to the kernel.
        unset($config['aspects']);

        return $config;
    }

    /**
     * Returns the path to the configuration
     */
    private function configPath(): string
    {
        return __DIR__ . '/../config/go_aop.php';
    }
}
