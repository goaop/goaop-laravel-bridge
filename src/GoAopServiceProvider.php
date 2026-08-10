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
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
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
            // The upstream init() signature is annotated with a narrower shape
            // than the kernel actually accepts (a literal-string "appDir" and
            // empty include/exclude path arrays), while the options here are
            // runtime values of the very types AspectKernel::normalizeOptions()
            // documents and expects.
            // @phpstan-ignore argument.type
            $kernel->init($this->kernelOptions());

            return $kernel;
        });
        $this->app->singleton(
            AspectContainer::class,
            static fn (Container $app): AspectContainer => $app->make(AspectKernel::class)->getContainer()
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
                AboutCommand::add('Go! AOP', function (): array {
                    $options = $this->kernelOptions();

                    return [
                        'Cache Directory' => $options['cacheDir'] ?? 'NOT CONFIGURED',
                        'Debug Mode' => $options['debug'] ? 'ENABLED' : 'OFF',
                    ];
                });
            }
        }
    }

    /**
     * Registers aspects declared in the "go_aop.aspects" config list and
     * services tagged with "goaop.aspect" in the aspect container.
     */
    private function registerAspects(): void
    {
        $aspectContainer = $this->app->make(AspectContainer::class);
        $registered = [];

        $register = function (mixed $aspect) use ($aspectContainer, &$registered): void {
            if (!$aspect instanceof Aspect) {
                throw new InvalidArgumentException(sprintf(
                    'Aspect "%s" must implement the "%s" interface.',
                    get_debug_type($aspect),
                    Aspect::class
                ));
            }
            if (!isset($registered[$aspect::class])) {
                $aspectContainer->registerAspect($aspect);
                $registered[$aspect::class] = true;
            }
        };

        foreach ($this->configuredAspectClasses() as $aspectClass) {
            $register($this->app->make($aspectClass));
        }

        foreach ($this->app->tagged('goaop.aspect') as $aspect) {
            $register($aspect);
        }
    }

    /**
     * Returns the list of aspect classes declared in the "go_aop.aspects" config.
     *
     * @return list<string>
     */
    private function configuredAspectClasses(): array
    {
        $configuredAspects = $this->config()->get('go_aop.aspects', []);
        if (!is_array($configuredAspects)) {
            throw new InvalidArgumentException(sprintf(
                'The "go_aop.aspects" configuration must be a list of aspect class names, "%s" given.',
                get_debug_type($configuredAspects)
            ));
        }

        $aspectClasses = [];
        foreach ($configuredAspects as $aspectClass) {
            if (!is_string($aspectClass)) {
                throw new InvalidArgumentException(sprintf(
                    'The "go_aop.aspects" configuration must contain class names, "%s" given.',
                    get_debug_type($aspectClass)
                ));
            }
            $aspectClasses[] = $aspectClass;
        }

        return $aspectClasses;
    }

    /**
     * Collects normalized kernel options from the merged configuration.
     *
     * The "aspects" list is a bridge-level concept unknown to the kernel and
     * is therefore not part of the returned options. Values that do not match
     * the type expected by the kernel fall back to the kernel defaults, so a
     * malformed configuration behaves exactly as a missing one.
     *
     * @return array{
     *     debug: bool,
     *     appDir: string,
     *     cacheDir: string|null,
     *     cacheFileMode?: int,
     *     features: int,
     *     includePaths: list<string>,
     *     excludePaths: list<string>,
     * }
     */
    private function kernelOptions(): array
    {
        $config = $this->config();

        $debug    = $config->get('go_aop.debug');
        $appDir   = $config->get('go_aop.appDir');
        $cacheDir = $config->get('go_aop.cacheDir');
        $features = $config->get('go_aop.features');

        $options = [
            'debug'        => is_bool($debug) ? $debug : false,
            'appDir'       => is_string($appDir) ? $appDir : '',
            'cacheDir'     => is_string($cacheDir) ? $cacheDir : null,
            'features'     => is_int($features) ? $features : 0,
            'includePaths' => $this->pathListOption('go_aop.includePaths'),
            'excludePaths' => $this->pathListOption('go_aop.excludePaths'),
        ];

        // Leave the key out for non-int values, so the kernel can apply its
        // own umask-based default instead of an arbitrary file mode.
        $cacheFileMode = $config->get('go_aop.cacheFileMode');
        if (is_int($cacheFileMode)) {
            $options['cacheFileMode'] = $cacheFileMode;
        }

        return $options;
    }

    /**
     * Reads a list of directories from the given configuration key.
     *
     * @return list<string>
     */
    private function pathListOption(string $key): array
    {
        $paths = $this->config()->get($key, []);

        return is_array($paths) ? array_values(array_filter($paths, is_string(...))) : [];
    }

    /**
     * Returns the application configuration repository
     */
    private function config(): ConfigRepository
    {
        return $this->app->make(ConfigRepository::class);
    }

    /**
     * Returns the path to the configuration
     */
    private function configPath(): string
    {
        return __DIR__ . '/../config/go_aop.php';
    }
}
