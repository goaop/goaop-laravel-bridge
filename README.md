# Go! AOP Laravel bridge

[![CI](https://github.com/goaop/goaop-laravel-bridge/actions/workflows/ci.yml/badge.svg)](https://github.com/goaop/goaop-laravel-bridge/actions/workflows/ci.yml)
![PHPStan Badge](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg?style=flat&link=https%3A%2F%2Fphpstan.org%2Fuser-guide%2Frule-levels)
[![GitHub release](https://img.shields.io/github/release/goaop/goaop-laravel-bridge.svg)](https://github.com/goaop/goaop-laravel-bridge/releases/latest)
[![Total Downloads](https://img.shields.io/packagist/dt/goaop/goaop-laravel-bridge.svg)](https://packagist.org/packages/goaop/goaop-laravel-bridge)
[![Monthly Downloads](https://img.shields.io/packagist/dm/goaop/goaop-laravel-bridge.svg)](https://packagist.org/packages/goaop/goaop-laravel-bridge)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.4-8892BF.svg)](https://www.php.net/supported-versions.php)
[![License](https://img.shields.io/packagist/l/goaop/goaop-laravel-bridge.svg)](https://packagist.org/packages/goaop/goaop-laravel-bridge)
[![Sponsor](https://img.shields.io/badge/Sponsor-❤️-lightgray?style=flat&logo=github)](https://github.com/sponsors/lisachenko)

Integration bridge for the [Go! AOP framework](https://github.com/goaop/framework) and Laravel. It boots the AOP engine early in the application lifecycle so aspects are woven into your classes transparently — no code generation steps, no manual proxies.

## Why Aspect-Oriented Programming in Laravel?

Some logic doesn't belong to any single class, yet ends up copied into hundreds of them: logging, caching, metrics, transactions, authorization checks, rate limiting, retry policies. Laravel's middleware solves this for HTTP requests — AOP solves it for **any method in your codebase**. Define the behavior once as an aspect, declare *where* it applies with a pointcut expression, and the engine weaves it in automatically. Your services stay pure business logic.

### ✨ Highlights

- 🪡 **Transparent weaving** — aspects are applied by transforming classes at load time. No base classes to extend, no interfaces to implement, no `Proxy` wrappers to maintain, no changes to how you `new` or inject your services.
- 🏷️ **Modern PHP 8 attributes** — advices are declared with `#[Before]`, `#[After]`, `#[Around]` and `#[AfterThrowing]` right on your aspect methods, with a powerful regexp-like pointcut syntax (`execution(public App\Services\**->*(*))`).
- 🧩 **Laravel-native integration** — package auto-discovery, publishable config, aspects resolved through the container (constructor DI works), registration via a simple config list or the `goaop.aspect` service tag, `php artisan about` support.
- 🚀 **Production-friendly** — pre-generate all proxies at deploy time with `php artisan aop:warmup` (in parallel workers, with an `opcache.preload` script on request); with `debug` off the woven code is served straight from cache and plays nicely with opcache.
- 🔬 **Pure PHP, no magic runtime** — built on Go! AOP 4.x: no PECL extensions, no `eval()`, all transformations produce reviewable static PHP files.
- ✅ **Proven by tests** — the bridge ships with end-to-end weaving tests (advice execution, `Around` return rewriting, cache generation) running against Laravel 12 and 13 on PHP 8.4.

## Requirements

- PHP >= 8.4
- Laravel 12 or 13
- goaop/framework ^4.0 (attribute-based aspects), installed with the bridge

## Installation

```bash
composer require goaop/goaop-laravel-bridge
```

The service provider is registered automatically via package discovery.

Publish the configuration if you want to tweak it:

```bash
php artisan vendor:publish --tag=goaop-config
```

## Defining an aspect

Aspects are plain classes implementing `Go\Aop\Aspect` whose advice methods are declared with PHP 8 attributes (`#[Before]`, `#[After]`, `#[Around]`, `#[AfterThrowing]`):

```php
<?php

namespace App\Aspects;

use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Attribute\Around;
use Go\Lang\Attribute\Before;
use Illuminate\Support\Facades\Log;

class LoggingAspect implements Aspect
{
    #[Before('execution(public App\Services\**->*(*))')]
    public function logMethodCall(MethodInvocation $invocation): void
    {
        Log::debug('Calling ' . $invocation->getMethod()->getName());
    }

    #[Around('execution(public App\Services\PaymentService->charge(*))')]
    public function measureCharge(MethodInvocation $invocation): mixed
    {
        $start = hrtime(true);
        try {
            return $invocation->proceed();
        } finally {
            Log::info('charge() took ' . (hrtime(true) - $start) / 1e6 . 'ms');
        }
    }
}
```

See the Go! AOP documentation on [core concepts](https://github.com/goaop/framework#core-concepts) and [creating aspects](https://github.com/goaop/framework#4-create-an-aspect) for the full pointcut expression syntax.

## Auto-discovery of aspects

You never register aspects with the AOP engine manually. During the application's boot phase — after all service providers have registered, before your application code runs — the bridge collects aspects from two sources and hands each of them to the engine's aspect container:

1. **The `go_aop.aspects` config list.** Every class listed here is registered with the engine as soon as the AOP kernel starts, before any service provider boots, without loading the class, and resolved through the Laravel service container on the first call of one of its advices. Constructor dependency injection works out of the box, and an aspect may depend on services that only become available once the application has fully booted:

   ```php
   // config/go_aop.php
   'aspects' => [
       App\Aspects\LoggingAspect::class,
   ],
   ```

2. **The `goaop.aspect` container tag.** Any service tagged with `goaop.aspect` in one of your service providers is picked up automatically — useful when an aspect needs non-trivial construction logic, or when a package wants to contribute aspects without touching your config:

   ```php
   // app/Providers/AppServiceProvider.php — in register()
   $this->app->singleton(LoggingAspect::class, function ($app) {
       return new LoggingAspect($app->make(LoggerInterface::class));
   });
   $this->app->tag([LoggingAspect::class], 'goaop.aspect');
   ```

   Tagged services are resolved when the bridge's provider boots (a Laravel tag can only be enumerated by resolving its services), so prefer the config list for aspects with heavy or boot-dependent constructors.

Both sources can be combined; duplicates are registered only once. Every discovered class must implement `Go\Aop\Aspect`, otherwise the bridge fails with a descriptive exception: a tagged service when the provider boots, a class of the config list when `aop:warmup` runs, in debug mode when the kernel starts, and otherwise when the aspect is resolved. The Laravel container is attached to the engine's aspect container as its PSR-11 delegate, so advices and aspect factories can look up application services through it. Once registered, the engine reads the `#[Before]`/`#[After]`/`#[Around]`/`#[AfterThrowing]` attributes from the aspect's methods and weaves the advices into every class matched by their pointcut expressions as it is loaded (or ahead of time via [`aop:warmup`](#deployment)).

## Configuration

Key options in `config/go_aop.php` (all overridable via env):

| Option | Default | Purpose |
|---|---|---|
| `debug` | `GOAOP_DEBUG` → `APP_DEBUG` | Re-weave when sources change; keep off in production |
| `appDir` | `base_path()` | Root directory the weaver may touch |
| `cacheDir` | `storage_path('framework/aop')` (`GOAOP_CACHE_DIR`) | Where woven sources and proxies are cached |
| `cacheFileMode` | `0770` (`GOAOP_CACHE_PERMISSIONS`, octal digits, e.g. `"770"`) | chmod for cache files |
| `parsedFilesLimit` | `100` (`GOAOP_PARSED_FILES_LIMIT`) | Parsed source files (~0.2 MB each) kept in memory while weaving: more re-parses less, fewer uses less memory; the default of `aop:warmup --cached-files` |
| `includePaths` | `[app_path()]` | Only these directories are woven |
| `excludePaths` | `[]` | Never weave these |
| `features` | `0` (`GOAOP_FEATURES`) | Bitmask of `Go\Aop\Features` engine features (`INTERCEPT_INCLUDES` = 4, `PREBUILT_CACHE` = 64, `PARTIAL_REFLECTION` = 256; function and `new` interception is driven by pointcuts and needs no flag) |
| `aspects` | `[]` | Aspect classes to auto-register |

## Deployment

Weaving happens lazily on first load of each class. To pre-generate all proxies during deployment (recommended with `debug => false`):

```bash
php artisan aop:warmup
```

The command exits with a non-zero status when a file cannot be woven, so a deploy script stops instead of shipping an incomplete cache. Weaving errors are written to the error output; pass `--fail-fast` to stop at the first one.

Large applications are woven faster in parallel worker processes:

```bash
php artisan aop:warmup --workers=8
```

Every worker is the application's own `artisan` running the hidden `aop:warmup-worker` command, so it boots with the same environment, configuration and aspects as the command itself (an `--env` option is passed on). The command hands the files out, merges the cache records of all workers and writes the cache metadata once at the end. Each weaving process keeps the last `parsedFilesLimit` parsed files in memory (100 by default); raise `--cached-files` to re-parse less, lower it to use less memory.

To prepare the cache of a new release next to the running one, build it in another directory:

```bash
php artisan aop:warmup --cache-dir=/srv/app/releases/next/storage/framework/aop
```

The kernel (and the workers) keep serving from their own directory while the other one is built from scratch, and a cache built this way is complete and location independent.

With `--preload` the command also writes an [`opcache.preload`](https://www.php.net/manual/en/opcache.preloading.php) script of the cache after a complete warm-up: `_preload.php` in the cache directory being built, or the file given as `--preload=FILE`. Point `opcache.preload` (with `opcache.preload_user`) at it and every woven class is linked once when PHP starts instead of being loaded by each request:

```bash
php artisan aop:warmup --preload
```

To ship compiled scripts with a container image, prime the [`opcache.file_cache`](https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.file-cache) after the warm-up and after `php artisan optimize`, in the final image, with the PHP binary that serves the requests:

```bash
php artisan optimize
mkdir -p /var/cache/opcache && php -d opcache.enable_cli=1 -d opcache.file_cache_only=1 \
    -d opcache.file_cache=/var/cache/opcache \
    artisan aop:warmup-opcache --file-cache=/var/cache/opcache
```

`aop:warmup-opcache` is the framework's `cache:warmup:opcache` run on the kernel of the application: it compiles the AOP cache, Composer's class map and autoloaded files, the unwoven sources below the include paths, the files of Laravel and the `--entry` files. The files of Laravel are `public/index.php`, `artisan`, every PHP file below `bootstrap/` (with the caches written by `optimize`: `config.php`, `services.php`, `packages.php`, `routes-v7.php`, `events.php`, also when `APP_CONFIG_CACHE` and friends move them elsewhere), `config/` and `routes/`; pass `--entry` for any other file a request includes. Compiled scripts land on disk only in a process started with these settings; without them the command fails and prints the command line to run.

With `PREBUILT_CACHE` (`features` = 64) a request never weaves: a class below the include paths that the cache misses fails with a `WeavingException`, so run `aop:warmup` on every deployment. The command itself (and its workers) still boots the application before it weaves: the classes the application loads while it boots are served from their original source in that process, and woven into the cache by the build.

Laravel's cache commands (`package:discover`, which `composer install` runs, `optimize`, `optimize:clear` and the `config`, `event`, `route` and `view` `:cache`/`:clear` commands they run) are treated the same way: they write caches and serve no request, so the classes the application loads while it boots (event discovery reflects the listeners) are served from their original source there. The configured aspects are not loaded when the kernel starts. Any other command, and every request, still fails on a class the cache misses. A deployment with `GOAOP_FEATURES=64` in its environment therefore runs in the natural order:

```bash
composer install --no-dev --optimize-autoloader   # runs package:discover, with an empty AOP cache
php artisan optimize                               # the cached configuration carries GOAOP_FEATURES
php artisan aop:warmup
```

Once the configuration is cached, `env()` is not read any more: run `optimize` (or `config:cache`) with the production environment, `GOAOP_FEATURES` included.

`php artisan about` shows the current AOP cache location and debug mode.

## Caveats

- Weaving starts at the beginning of the application's boot phase. Classes that are already loaded before that (very early bootstrap code, other packages' `register()` internals) cannot be woven.
- Only classes under `appDir` + `includePaths` are considered — vendor code is not woven.
- Exception handlers are best excluded via `excludePaths`: when a fatal error occurs, a woven handler may not be loadable from a cold cache.

## License

MIT — see [LICENSE](LICENSE).
