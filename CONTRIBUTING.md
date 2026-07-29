# Contributing

Thanks for helping improve the Go! AOP Laravel bridge!

## Workflow

1. Fork/branch off `master`.
2. Name your branch using **conventional branch names** (see below).
3. Make your changes with **conventional commits**.
4. Ensure the suite is green locally: `composer validate --strict && composer test && composer analyse`.
5. Open a pull request. The PR title must be a valid conventional commit subject — CI checks it.

## Conventional commits

Every commit message follows [Conventional Commits 1.0.0](https://www.conventionalcommits.org/en/v1.0.0/):

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

Allowed types:

| Type | Use for |
|---|---|
| `feat` | new user-facing functionality |
| `fix` | bug fixes |
| `chore` | maintenance that touches neither src behavior nor CI |
| `ci` | CI workflows and build tooling |
| `docs` | documentation only |
| `test` | adding or fixing tests only |
| `refactor` | behavior-preserving code changes |
| `build` | composer/dependency/packaging changes |

Breaking changes: append `!` after the type/scope (e.g. `feat!: drop Laravel 11 support`) and add a `BREAKING CHANGE:` footer explaining the migration.

Examples:

```
feat: register aspects declared in go_aop.aspects config
fix(config): cast GOAOP_CACHE_PERMISSIONS env value to int
ci: add PHP 8.5 experimental job to the matrix
```

## Conventional branches

Branch names mirror commit types, kebab-case after the slash:

```
feat/aspect-auto-discovery
fix/cache-file-mode-cast
chore/update-dependabot
ci/php85-experimental
docs/readme-attributes-example
```

## Pull requests

- Keep PRs focused on one concern; stack PRs rather than mixing streams.
- Fill in the PR template; link the issue the PR addresses (`Closes #N`).
- Squash-merge is preferred; the squash commit message must itself be a valid conventional commit (the PR title is used, hence the title check).

## Testing notes

- Tests are built on [orchestra/testbench](https://github.com/orchestral/testbench).
- Anything that initializes `AspectKernel` must run with `#[RunTestsInSeparateProcesses]` — the kernel is a process-global singleton and it wraps the composer autoloader.
- Weaving fixtures must not be autoloaded before kernel init; keep them out of any eagerly-loaded code path.
