# Agent guidance for goaop/goaop-laravel-bridge

This file is the canonical guidance for AI coding agents (and a quick orientation for humans) working on this repository.

## What this package is

An integration bridge that plugs the [Go! AOP framework](https://github.com/goaop/framework) into Laravel applications. It boots the AOP kernel early in the Laravel lifecycle so that the Go! AOP composer class loader can weave aspects into application classes transparently — no code generation steps, no manual proxy wiring for the application developer.

- Target runtime: PHP >= 8.4, Laravel 12/13, goaop/framework 4.x (attribute-based aspects only).
- Package type: Laravel library package with auto-discovery (`extra.laravel.providers` in `composer.json`).

## Architecture map

The bridge is intentionally small. Everything lives under `src/` (PSR-4 namespace `Go\Laravel\GoAopBridge\`):

- `src/GoAopServiceProvider.php` — the single entry point. `register()` merges package config and schedules kernel initialization via `$this->app->booting(...)`, which fires at the very start of the boot phase (after all providers have registered, before any of them boots) — the kernel wraps the composer autoloader (`Go\Instrument\ClassLoading\AopComposerLoader`), so it must run before application classes are autoloaded. `boot()` registers aspects (from the `go_aop.aspects` config list and from services tagged `goaop.aspect`), publishes the config and wires the `aop:warmup` command.
- `src/Kernel/AspectLaravelKernel.php` — thin `Go\Core\AspectKernel` subclass. Aspect registration happens through the service provider, not `configureAop()`.
- `config/go_aop.php` — publishable config: `debug`, `appDir`, `cacheDir`, `cacheFileMode`, `includePaths`, `excludePaths`, `features`, `aspects`.

Load-bearing constraints (do not "simplify" these away):

- The kernel must be initialized at the start of the boot phase (via the `booting` callback), never lazily on first use. Weaving only applies to classes loaded *after* init. It cannot happen inside `register()` itself either: testbench (and environment-specific config in general) applies configuration after provider registration, so register-time init would freeze the default config.
- `Go\Core\AspectKernel` is a process-global singleton with a `final protected` constructor. Tests touching the kernel must run in separate PHPUnit processes.
- `cacheFileMode` must reach the kernel as an `int`; `env()` values from `.env` arrive as strings.
- Aspects are plain classes implementing `Go\Aop\Aspect` with advice declared via PHP 8 attributes (`Go\Lang\Attribute\Before`, `After`, `Around`, `AfterThrowing`, `Pointcut`). Doctrine-style annotations are not supported anywhere.

## Development commands

```bash
composer install            # needs minimum-stability: dev deps (goaop/framework 4.x-dev)
composer validate --strict
composer test               # phpunit
composer analyse            # phpstan
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
- The upstream 4.x line is unreleased: composer requires `minimum-stability: dev` + `prefer-stable: true`. Do not "fix" that to stable until goaop/framework 4.0 is tagged.
