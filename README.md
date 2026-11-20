# Go! AOP Laravel bridge

[![CI](https://github.com/goaop/goaop-laravel-bridge/actions/workflows/ci.yml/badge.svg)](https://github.com/goaop/goaop-laravel-bridge/actions/workflows/ci.yml)
![PHPStan Badge](https://img.shields.io/badge/PHPStan-level%20max-brightgreen.svg?style=flat&link=https%3A%2F%2Fphpstan.org%2Fuser-guide%2Frule-levels)
[![GitHub release](https://img.shields.io/github/release/goaop/goaop-laravel-bridge.svg)](https://github.com/goaop/goaop-laravel-bridge/releases/latest)
[![Total Downloads](https://img.shields.io/packagist/dt/goaop/goaop-laravel-bridge.svg)](https://packagist.org/packages/goaop/goaop-laravel-bridge)
[![Monthly Downloads](https://img.shields.io/packagist/dm/goaop/goaop-laravel-bridge.svg)](https://packagist.org/packages/goaop/goaop-laravel-bridge)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.4-8892BF.svg)](https://www.php.net/supported-versions.php)
[![License](https://img.shields.io/packagist/l/goaop/goaop-laravel-bridge.svg)](https://packagist.org/packages/goaop/goaop-laravel-bridge)
[![Sponsor](https://img.shields.io/badge/Sponsor-❤️-lightgray?style=flat&logo=github)](https://github.com/sponsors/lisachenko)

Middleware for any method of your Laravel application, not just your routes. The bridge plugs [Go! AOP](https://github.com/goaop/framework) into Laravel: you write a behavior once, say where it applies, and it runs around those methods as dynamic middleware, in services, actions, jobs and any other class in `app/`. No base classes, no decorators, no code generation step in development.

Full documentation: **[aopphp.com/docs/integrations/laravel](https://aopphp.com/docs/integrations/laravel/)**.

```php
// app/Services/OrderService.php
use App\Attributes\Transactional;

final class OrderService
{
    #[Transactional]
    public function place(Cart $cart): Order
    {
        $order = Order::create(['customer_id' => $cart->customerId]);
        $order->lines()->createMany($cart->lines());

        return $order;
    }
}
```

```php
// app/Behaviors/TransactionalBehavior.php
namespace App\Behaviors;

use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Attribute\Around;
use Illuminate\Support\Facades\DB;

final class TransactionalBehavior implements Aspect
{
    #[Around('@execution(App\Attributes\Transactional)')]
    public function inTransaction(MethodInvocation $invocation): mixed
    {
        return DB::transaction(fn () => $invocation->proceed());
    }
}
```

If you have written a middleware's `handle($request, $next)`, you already know the pattern: `$invocation->proceed()` is your `$next($request)`. Every method marked `#[Transactional]` now commits or rolls back as a unit, and `OrderService` keeps only its business logic.

## Why behaviors in Laravel?

Some logic doesn't belong to any single class, yet ends up copied into hundreds of them: logging, caching, metrics, transactions, authorization checks, rate limiting, retry policies. Laravel's middleware solves this for HTTP requests. Behaviors solve it for **any method or property in your codebase**, public, protected or private: define the behavior once, select the methods and properties with a matching rule (by attribute, name, namespace or visibility), and the engine attaches it as the classes load.

### It feels like part of Laravel

- **Installs like any other package.** `composer require`, and package discovery registers the service provider. One publishable config file, `config/go_aop.php`, with `GOAOP_*` environment overrides.
- **Behavior classes are container services.** The Laravel container builds each behavior class the first time one of its behaviors runs: constructor injection, facades, `config()` and every binding of your application work.
- **Registered where you register everything else.** A config list, or the `goaop.aspect` service tag, so packages can contribute their own behaviors.
- **Artisan does the production work.** `php artisan aop:warmup` builds the cache at deploy time (in parallel workers, with an `opcache.preload` script on request) and fits right after `composer install` and `php artisan optimize`. `php artisan about` shows the cache location and debug mode.
- **No build step in development.** With `debug` on, a changed class is compiled again on the next request, and breakpoints work in your original files.

### It reaches what a decorator can't

- **Every instance gets the behavior.** Behaviors are attached to the class itself while it loads, so objects from the container and objects you create with `new` behave the same. Type hints, `instanceof` and the class name are unchanged: no wrapper object exists.
- **Every kind of method qualifies.** Private, protected, `final` and static methods, and methods of `final` and `readonly` classes.
- **Properties too.** Reads and writes of public, protected and private properties, constructor-promoted ones included, through native PHP 8.4 property hooks: `access(private App\Domain\Order->status)` or `@access(App\Attributes\Audited)`.

### Built on solid ground

- **Modern PHP 8 attributes.** Behaviors are declared with `#[Before]`, `#[After]`, `#[Around]` and `#[AfterThrowing]`, with an expressive matching-rule syntax (`execution(public App\Services\**->*(*))`, `@execution(App\Attributes\Transactional)`).
- **Small cost.** Methods and properties without behaviors cost nothing extra; a method or property with a behavior pays under 1 µs per call ([measured costs](https://aopphp.com/docs/production/#cost-per-call)).
- **Pure PHP, no magic runtime.** No PECL extensions, no `eval()`: the compiled classes are static PHP files you can read and review.
- **Proven by tests.** The bridge ships with end-to-end tests (behavior execution, `Around` return values, class extensions, cache generation, parallel warm-up) running against Laravel 12 and 13 on PHP 8.4.

## Requirements

- PHP >= 8.4
- Laravel 12 or 13
- goaop/framework ^4.0 (behaviors declared with attributes), installed with the bridge

## Installation

```bash
composer require goaop/goaop-laravel-bridge
```

The service provider is registered automatically via package discovery.

Publish the configuration if you want to tweak it:

```bash
php artisan vendor:publish --tag=goaop-config
```

## Your first behavior

A **behavior class** (an *aspect*, in the framework's API) is a plain class that implements `Go\Aop\Aspect`. Each of its public methods with `#[Before]`, `#[After]`, `#[Around]` or `#[AfterThrowing]` is one **behavior** (an *advice*), and the string in that attribute is its **matching rule** (a *pointcut*): it says where the behavior runs.

1. Create an attribute to mark the methods (or skip it and select methods by name, as below):

   ```php
   // app/Attributes/Transactional.php
   namespace App\Attributes;

   use Attribute;

   #[Attribute(Attribute::TARGET_METHOD)]
   final class Transactional {}
   ```

2. Write the behavior class: `TransactionalBehavior` above is complete.

3. Register it in `config/go_aop.php`:

   ```php
   'aspects' => [
       App\Behaviors\TransactionalBehavior::class,
   ],
   ```

4. Mark a method with `#[Transactional]`. The next request runs it inside a transaction.

A behavior doesn't need an attribute on the target. A matching rule can select methods by name, namespace or visibility, so a whole layer of the application gets the behavior without any change to its classes:

```php
<?php

namespace App\Behaviors;

use Go\Aop\Aspect;
use Go\Aop\Intercept\MethodInvocation;
use Go\Lang\Attribute\Around;
use Go\Lang\Attribute\Before;
use Illuminate\Support\Facades\Log;

final class ServiceTimingBehavior implements Aspect
{
    #[Before('execution(public App\Services\**->*(*))')]
    public function logCall(MethodInvocation $invocation): void
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
            Log::info('charge() took ' . (hrtime(true) - $start) / 1e6 . ' ms');
        }
    }
}
```

See [Behaviors & Matching Rules](https://aopphp.com/docs/behaviors/) for the four attributes, the call context (`$invocation`) and the full matching-rule syntax, and the walkthroughs of a [caching behavior](https://aopphp.com/docs/behaviors/caching/) and a [logging behavior](https://aopphp.com/docs/behaviors/logging/).

## Registering behavior classes

You never hand behavior classes to the engine yourself. During the application's boot phase — after all service providers have registered, before your application code runs — the bridge collects them from two sources and hands each of them to the engine's aspect container:

1. **The `go_aop.aspects` config list.** Every class listed here is registered with the engine as soon as the kernel starts, before any service provider boots, without loading the class, and resolved through the Laravel service container on the first call of one of its behaviors. Constructor dependency injection works out of the box, and a behavior class may depend on services that only become available once the application has fully booted:

   ```php
   // config/go_aop.php
   'aspects' => [
       App\Behaviors\ServiceTimingBehavior::class,
   ],
   ```

2. **The `goaop.aspect` container tag.** Any service tagged with `goaop.aspect` in one of your service providers is picked up automatically — useful when a behavior class needs non-trivial construction logic, or when a package wants to contribute behaviors without touching your config:

   ```php
   // app/Providers/AppServiceProvider.php — in register()
   $this->app->singleton(ServiceTimingBehavior::class, function ($app) {
       return new ServiceTimingBehavior($app->make(LoggerInterface::class));
   });
   $this->app->tag([ServiceTimingBehavior::class], 'goaop.aspect');
   ```

   Tagged services are resolved when the bridge's provider boots (a Laravel tag can only be enumerated by resolving its services), so prefer the config list for behavior classes with heavy or boot-dependent constructors.

Both sources can be combined; duplicates are registered only once. Every discovered class must implement `Go\Aop\Aspect`, otherwise the bridge fails with a descriptive exception: a tagged service when the provider boots, a class of the config list when `aop:warmup` runs, in debug mode when the kernel starts, and otherwise when the behavior class is resolved. The Laravel container is attached to the engine's aspect container as its PSR-11 delegate, so behaviors and the factories of behavior classes can look up application services through it. Once registered, the engine reads the `#[Before]`/`#[After]`/`#[Around]`/`#[AfterThrowing]` attributes from the behavior class and attaches each behavior to every method its matching rule selects, as the class is loaded (or ahead of time via [`aop:warmup`](#deployment)).

## Configuration

Key options in `config/go_aop.php` (all overridable via env):

| Option | Default | Purpose |
|---|---|---|
| `debug` | `GOAOP_DEBUG` → `APP_DEBUG` | Compile again when sources change, with breakpoints in the original files; keep off in production |
| `appDir` | `base_path()` | Root directory the compiler may touch |
| `cacheDir` | `storage_path('framework/aop')` (`GOAOP_CACHE_DIR`) | Where compiled sources and proxies are cached |
| `cacheFileMode` | `0770` (`GOAOP_CACHE_PERMISSIONS`, octal digits, e.g. `"770"`) | chmod for cache files |
| `parsedFilesLimit` | `100` (`GOAOP_PARSED_FILES_LIMIT`) | Parsed source files (~0.2 MB each) kept in memory while compiling: more re-parses less, fewer uses less memory; the default of `aop:warmup --cached-files` |
| `includePaths` | `[app_path()]` | Only these directories are compiled |
| `excludePaths` | `[]` | Never compile these |
| `features` | `0` (`GOAOP_FEATURES`) | Bitmask of `Go\Aop\Features` engine features (`INTERCEPT_INCLUDES` = 4, `PREBUILT_CACHE` = 64, `PARTIAL_REFLECTION` = 256; behaviors on function calls and object creation are driven by their matching rules and need no flag) |
| `aspects` | `[]` | Behavior classes to register |

## Deployment

Without a warm-up, each class is compiled on its first load. To compile them all up front during deployment (recommended with `debug => false`):

```bash
php artisan aop:warmup
```

The command exits with a non-zero status when a file cannot be compiled, so a deploy script stops instead of shipping an incomplete cache. Compile errors are written to the error output; pass `--fail-fast` to stop at the first one.

Large applications compile faster in parallel worker processes:

```bash
php artisan aop:warmup --workers=8
```

Every worker is the application's own `artisan` running the hidden `aop:warmup-worker` command, so it boots with the same environment, configuration and behavior classes as the command itself (an `--env` option is passed on). The command hands the files out, merges the cache records of all workers and writes the cache metadata once at the end. Each compiling process keeps the last `parsedFilesLimit` parsed files in memory (100 by default); raise `--cached-files` to re-parse less, lower it to use less memory.

To prepare the cache of a new release next to the running one, build it in another directory:

```bash
php artisan aop:warmup --cache-dir=/srv/app/releases/next/storage/framework/aop
```

The kernel (and the workers) keep serving from their own directory while the other one is built from scratch, and a cache built this way is complete and location independent.

With `--preload` the command also writes an [`opcache.preload`](https://www.php.net/manual/en/opcache.preloading.php) script of the cache after a complete warm-up: `_preload.php` in the cache directory being built, or the file given as `--preload=FILE`. Point `opcache.preload` (with `opcache.preload_user`) at it and every compiled class is linked once when PHP starts instead of being loaded by each request:

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

`aop:warmup-opcache` is the framework's `cache:warmup:opcache` run on the kernel of the application: it compiles the framework's cache, Composer's class map and autoloaded files, the original sources below the include paths, the files of Laravel and the `--entry` files. The files of Laravel are `public/index.php`, `artisan`, every PHP file below `bootstrap/` (with the caches written by `optimize`: `config.php`, `services.php`, `packages.php`, `routes-v7.php`, `events.php`, also when `APP_CONFIG_CACHE` and friends move them elsewhere), `config/` and `routes/`; pass `--entry` for any other file a request includes. Compiled scripts land on disk only in a process started with these settings; without them the command fails and prints the command line to run.

With `PREBUILT_CACHE` (`features` = 64) a request never compiles: a class below the include paths that the cache misses fails with a `WeavingException`, so run `aop:warmup` on every deployment. The command itself (and its workers) still boots the application before it compiles: the classes the application loads while it boots are served from their original source in that process, and compiled into the cache by the build.

Laravel's cache commands (`package:discover`, which `composer install` runs, `optimize`, `optimize:clear` and the `config`, `event`, `route` and `view` `:cache`/`:clear` commands they run) are treated the same way: they write caches and serve no request, so the classes the application loads while it boots (event discovery reflects the listeners) are served from their original source there. The configured behavior classes are not loaded when the kernel starts. Any other command, and every request, still fails on a class the cache misses. A deployment with `GOAOP_FEATURES=64` in its environment therefore runs in the natural order:

```bash
composer install --no-dev --optimize-autoloader   # runs package:discover, with an empty framework cache
php artisan optimize                               # the cached configuration carries GOAOP_FEATURES
php artisan aop:warmup
```

Once the configuration is cached, `env()` is not read any more: run `optimize` (or `config:cache`) with the production environment, `GOAOP_FEATURES` included.

`php artisan about` shows the current location of the framework's cache and debug mode.

## Caveats

- The compile step starts at the beginning of the application's boot phase. Classes that are already loaded before that (very early bootstrap code, other packages' `register()` internals, your own service providers) cannot get behaviors.
- Only classes under `appDir` + `includePaths` are considered — vendor code is not compiled.
- Exception handlers are best excluded via `excludePaths`: when a fatal error occurs, a compiled handler may not be loadable from a cold cache.

## License

MIT — see [LICENSE](LICENSE).
