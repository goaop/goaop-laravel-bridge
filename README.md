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
- 🚀 **Production-friendly** — pre-generate all proxies at deploy time with `php artisan aop:warmup`; with `debug` off the woven code is served straight from cache and plays nicely with opcache.
- 🔬 **Pure PHP, no magic runtime** — built on Go! AOP 4.x: no PECL extensions, no `eval()`, all transformations produce reviewable static PHP files.
- ✅ **Proven by tests** — the bridge ships with end-to-end weaving tests (advice execution, `Around` return rewriting, cache generation) running against Laravel 12 and 13 on PHP 8.4.

## Requirements

- PHP >= 8.4
- Laravel 12 or 13
- goaop/framework 4.x (attribute-based aspects)

## Installation

```bash
composer require goaop/goaop-laravel-bridge
```

The service provider is registered automatically via package discovery.

> **Note**
> Until goaop/framework 4.0 is tagged, the bridge depends on the unreleased
> `4.0-dev` line, so your application's `composer.json` needs:
>
> ```json
> "minimum-stability": "dev",
> "prefer-stable": true
> ```

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

1. **The `go_aop.aspects` config list.** Every class listed here is resolved through the Laravel service container, so constructor dependency injection works out of the box:

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

Both sources can be combined; duplicates are registered only once. Every discovered class must implement `Go\Aop\Aspect`, otherwise the bridge fails fast with a descriptive exception. Once registered, the engine reads the `#[Before]`/`#[After]`/`#[Around]`/`#[AfterThrowing]` attributes from the aspect's methods and weaves the advices into every class matched by their pointcut expressions as it is loaded (or ahead of time via [`aop:warmup`](#deployment)).

## Configuration

Key options in `config/go_aop.php` (all overridable via env):

| Option | Default | Purpose |
|---|---|---|
| `debug` | `GOAOP_DEBUG` → `APP_DEBUG` | Re-weave when sources change; keep off in production |
| `appDir` | `base_path()` | Root directory the weaver may touch |
| `cacheDir` | `storage_path('framework/aop')` (`GOAOP_CACHE_DIR`) | Where woven sources and proxies are cached |
| `cacheFileMode` | `0770` (`GOAOP_CACHE_PERMISSIONS`, octal digits, e.g. `"770"`) | chmod for cache files |
| `includePaths` | `[app_path()]` | Only these directories are woven |
| `excludePaths` | `[]` | Never weave these |
| `features` | `0` (`GOAOP_FEATURES`) | Bitmask of `Go\Aop\Features` engine features |
| `aspects` | `[]` | Aspect classes to auto-register |

## Deployment

Weaving happens lazily on first load of each class. To pre-generate all proxies during deployment (recommended with `debug => false`):

```bash
php artisan aop:warmup
```

`php artisan about` shows the current AOP cache location and debug mode.

## Caveats

- Weaving starts at the beginning of the application's boot phase. Classes that are already loaded before that (very early bootstrap code, other packages' `register()` internals) cannot be woven.
- Only classes under `appDir` + `includePaths` are considered — vendor code is not woven.
- Exception handlers are best excluded via `excludePaths`: when a fatal error occurs, a woven handler may not be loadable from a cold cache.

## License

MIT — see [LICENSE](LICENSE).
