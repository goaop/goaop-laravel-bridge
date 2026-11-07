# Agent guidance for goaop/goaop-laravel-bridge

This file is the canonical guidance for AI coding agents (and a quick orientation for humans) working on this repository.

## What this package is

An integration bridge that plugs the [Go! AOP framework](https://github.com/goaop/framework) into Laravel applications. It boots the AOP kernel early in the Laravel lifecycle so that the Go! AOP composer class loader can weave aspects into application classes transparently — no code generation steps, no manual proxy wiring for the application developer.

- Target runtime: PHP >= 8.4, Laravel 12/13, goaop/framework ^4.0 (attribute-based aspects only).
- Package type: Laravel library package with auto-discovery (`extra.laravel.providers` in `composer.json`).

## Architecture map

The bridge is intentionally small. Everything lives under `src/` (PSR-4 namespace `Go\Laravel\GoAopBridge\`):

- `src/GoAopServiceProvider.php` — the single entry point. `register()` merges package config and schedules kernel initialization via `$this->app->booting(...)`, which fires at the very start of the boot phase (after all providers have registered, before any of them boots) — the kernel wraps the composer autoloader (`Go\Instrument\ClassLoading\AopComposerLoader`), so it must run before application classes are autoloaded. Right after `init()`, the Laravel container is attached as the PSR-11 delegate of the aspect container and the `go_aop.aspects` classes are registered with `addLazyService()`: weaving only reads the aspect classes, and each aspect is resolved through the Laravel container on the first call of one of its advices. `boot()` registers the services tagged `goaop.aspect` (a Laravel tag can only be enumerated by resolving its services, so these are created at boot), publishes the config and wires the `aop:warmup`, `aop:warmup-worker` and `aop:warmup-opcache` commands.
- `src/Console/WarmupCommand.php` — `aop:warmup`: wraps the framework's `Go\Instrument\ClassLoading\CacheWarmer` with `--fail-fast`, `--workers`, `--cached-files` (default: the `parsedFilesLimit` option), `--cache-dir` and `--preload[=FILE]` (declared in the constructor: its `false` default tells the option left out from the bare `--preload`), and fails when a file cannot be woven. With `--workers=N` the workers are the application's `artisan` running `src/Console/WarmupWorkerCommand.php` (`aop:warmup-worker`, hidden): booting the application initializes the kernel of the worker with the same configuration and aspects, and the worker loop (`Go\Instrument\ClassLoading\Warmup\WarmupWorker::main()`) is handed the command's own, already included, class file as its "loader" so that nothing else is executed. The weaving tests replace the worker command line (`WarmupCommand::workerCommandLine()`) with `tests/Fixtures/bin/warmup-worker.php`, which boots a testbench application with the test configuration, because no `artisan` boots the test application.
- `src/Console/OpcacheWarmupCommand.php` — `aop:warmup-opcache`: the framework's `Go\Console\Command\OpcacheWarmupCommand` (`cache:warmup:opcache`, primes `opcache.file_cache`) renamed, with the `loader` argument dropped and the kernel of the application injected (`loadAspectKernel()` is a no-op). Tested end to end by `tests/OpcacheWarmupCommandTest.php` in processes started with the file cache settings.
- `src/Kernel/AspectLaravelKernel.php` — thin `Go\Core\AspectKernel` subclass. Aspect registration happens through the service provider, not `configureAop()`.
- `config/go_aop.php` — publishable config: `debug`, `appDir`, `cacheDir`, `cacheFileMode`, `parsedFilesLimit`, `includePaths`, `excludePaths`, `features`, `aspects`.

Load-bearing constraints (do not "simplify" these away):

- The kernel must be initialized at the start of the boot phase (via the `booting` callback), never lazily on first use. Weaving only applies to classes loaded *after* init. It cannot happen inside `register()` itself either: testbench (and environment-specific config in general) applies configuration after provider registration, so register-time init would freeze the default config.
- `Go\Core\AspectKernel` is a process-global singleton with a `final protected` constructor. Tests touching the kernel must run in separate PHPUnit processes.
- `cacheFileMode` and `parsedFilesLimit` must reach the kernel as an `int`; `env()` values from `.env` arrive as strings (the config casts them, the provider rejects a `parsedFilesLimit` below 1, as the kernel does). The kernel validates its options strictly: `features` may only contain `Go\Aop\Features` bits (`INTERCEPT_INCLUDES` = 4, `PREBUILT_CACHE` = 64, `PARTIAL_REFLECTION` = 256; the former function/`new` interception flags 1 and 2 were removed and now throw).
- Aspects must be registered in the aspect container under their class name, and never looked up with `has()` to deduplicate: with the Laravel delegate attached, `has()` also answers for Laravel bindings.
- `aop:warmup` must return a failure exit code when `CacheWarmer::warmUp()` reports errors, so deploys do not ship an incomplete cache.
- A process running `aop:warmup` or `aop:warmup-worker` (detected from `$_SERVER['argv']`, the first argument that is not an option) is marked with `CachePathManager::markBuildProcess()` before `init()`: with `Features::PREBUILT_CACHE` a class below the include paths that the cache misses fails the process with a `WeavingException`, and artisan boots the application, which may load such classes, before the command runs. Marked, they are served unwoven there and woven by the build (goaop/framework#808). Test scripts that start a worker without artisan (`tests/Fixtures/bin/warmup-worker.php`) mark the process themselves; `tests/PrebuiltCacheWarmupTest.php` runs `tests/Fixtures/bin/console.php`, an artisan-like console with a prebuilt cache.
- The warmup workers and the coordinator must use the same `appDir`: the cache records the workers report are keyed relative to it, and the coordinator merges them into its own cache metadata.
- Aspects are plain classes implementing `Go\Aop\Aspect` with advice declared via PHP 8 attributes (`Go\Lang\Attribute\Before`, `After`, `Around`, `AfterThrowing`, `Pointcut`). Doctrine-style annotations are not supported anywhere.

## Development commands

```bash
composer install
composer validate --strict
composer test               # phpunit
composer analyse            # phpstan 2, not a dev dependency: installed globally (CI: setup-php tools)
```

Tests use orchestra/testbench; weaving tests run in separate processes and write proxy caches to a per-test temp directory.

## Conventions

- **Conventional Commits 1.0.0** for every commit message: `type(optional-scope): description`. Allowed types: `feat`, `fix`, `chore`, `ci`, `docs`, `test`, `refactor`, `build`. Breaking changes: `!` after the type/scope and a `BREAKING CHANGE:` footer.
- **Conventional branch names**: `feat/…`, `fix/…`, `chore/…`, `ci/…`, `docs/…`, `test/…`, `refactor/…` (kebab-case after the slash).
- PR titles must themselves be valid conventional commit subjects — CI enforces this (`.github/workflows/lint-pr.yml`).
- See `CONTRIBUTING.md` for the full contributor workflow.

## Things agents get wrong here

- Do not register the service provider as deferrable; AOP initialization must be eager.
- Do not autoload weaving-test fixture classes before the kernel is initialized (no `use`-triggering references at file top level in test bootstrap).
- Do not add `containerClass` back to the config with `Go\Core\GoAspectContainer` — that class was removed upstream; the framework default (`Go\Core\Container`) is correct.
- The bridge requires the stable goaop/framework `^4.0`, and `symfony/console` `^7.4|^8.0` for the framework console classes it runs (`OpcacheWarmupCommand`, the warm-up output): do not bring back `minimum-stability: dev`. A framework feature that is not tagged yet is adopted once it is, with a raised `goaop/framework` constraint.
