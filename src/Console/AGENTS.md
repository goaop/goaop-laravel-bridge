# Agent guidance for `src/Console/`

The artisan commands of the bridge. They run on the kernel of the application: artisan boots the application, and the
service provider initializes the kernel while it boots, before a command runs.

- `WarmupCommand.php` — `aop:warmup`: wraps the framework's `Go\Instrument\ClassLoading\CacheWarmer` with
  `--fail-fast`, `--workers`, `--cached-files` (default: the `parsedFilesLimit` option of the kernel), `--cache-dir`
  and `--preload[=FILE]`, and fails when a file cannot be woven (a deploy must never ship an incomplete cache).
  `--preload` is declared in the constructor, not in the signature: its `false` default tells the option left out
  from a bare `--preload`, which Laravel's signature syntax can not express.
- `WarmupWorkerCommand.php` — `aop:warmup-worker` (hidden): the worker processes of `aop:warmup --workers=N` are the
  application's `artisan` running this command. Booting the application initializes the kernel of the worker with the
  same configuration and aspects (an `--env` option is passed on), and the worker loop
  (`Go\Instrument\ClassLoading\Warmup\WarmupWorker::main()`) is handed the command's own, already included, class file
  as its "loader", so that nothing else is executed. `--cached-files` defaults to the `parsedFilesLimit` option too.
- `OpcacheWarmupCommand.php` — `aop:warmup-opcache`: the framework's `Go\Console\Command\OpcacheWarmupCommand`
  (`cache:warmup:opcache`, primes `opcache.file_cache`) renamed into the `aop:` namespace, with the `loader` argument
  dropped and the kernel of the application injected (`loadAspectKernel()` is a no-op).

## Tests

- No `artisan` boots the test application: the weaving tests replace the worker command line
  (`WarmupCommand::workerCommandLine()`) with `tests/Fixtures/bin/warmup-worker.php`, which boots a testbench
  application with the test configuration. Started without artisan, that script marks its process as a build process
  itself (`CachePathManager::markBuildProcess()`).
- `tests/PrebuiltCacheWarmupTest.php` runs `tests/Fixtures/bin/console.php`, an artisan-like console with a prebuilt
  cache, to check that `aop:warmup` (in process and with workers) builds a cache the application loads classes of
  while it boots, and that other commands run before the cache is built. One of its aspects lies below the include
  paths (`tests/Fixtures/App`); environment variables of the console are described at the top of the script. A test
  running `optimize` must run `optimize:clear` afterwards: the testbench skeleton is shared by every test.
- `tests/OpcacheWarmupCommandTest.php` runs `aop:warmup-opcache` end to end, in processes started with the file cache
  settings.
