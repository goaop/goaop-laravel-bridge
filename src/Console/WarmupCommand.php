<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Console;

use Go\Aop\Exception\InvalidConfigurationException;
use Go\Core\AspectKernel;
use Go\Instrument\ClassLoading\CacheWarmer;
use Illuminate\Console\Command;

/**
 * Warms up the Go! AOP proxy cache ahead of time (e.g. during deployment),
 * so no weaving happens on the first real request.
 *
 * The command fails when a file cannot be woven, so a deploy script stops
 * instead of shipping an incomplete cache. Weaving errors are written to
 * the error output.
 *
 * With "--workers=N" the files are woven in N worker processes: each one
 * runs the hidden "aop:warmup-worker" artisan command, which boots the
 * application (and with it the AOP kernel) exactly like this process. This
 * process hands the files out, merges the cache records the workers report
 * and writes the cache metadata once at the end.
 *
 * With "--cache-dir=DIR" the cache is built in another directory, from
 * scratch, while the kernel keeps serving this process from its own one:
 * the way a deployment prepares the cache of the next release.
 */
class WarmupCommand extends Command
{
    protected $signature = 'aop:warmup
                            {--fail-fast : Stop after the first file that fails to weave}
                            {--w|workers=1 : Number of parallel worker processes}
                            {--cached-files= : Number of parsed files each weaving process keeps in memory: more is faster, fewer uses less memory (default: the "parsedFilesLimit" option)}
                            {--cache-dir= : Build the cache in this directory instead of the one of the kernel}';

    protected $description = 'Warm up the Go! AOP proxy cache for all weavable classes';

    public function handle(AspectKernel $aspectKernel): int
    {
        $workers     = $this->positiveIntegerOption('workers');
        // The explicit option wins, the parsedFilesLimit option of the kernel is the default
        $cachedFiles = $this->option('cached-files') === null
            ? $aspectKernel->getOptions()['parsedFilesLimit']
            : $this->positiveIntegerOption('cached-files');
        $cacheDir    = $this->option('cache-dir');
        $cacheDir    = is_string($cacheDir) && $cacheDir !== '' ? $cacheDir : null;

        $workerCommandLine = [];
        if ($workers > 1) {
            $workerCommandLine = $this->workerCommandLine($cachedFiles, $cacheDir);
            if ($workerCommandLine === []) {
                $this->warn('Worker processes cannot be started from here, weaving in this process instead.');
            }
        }

        $warmer = new CacheWarmer(
            $aspectKernel,
            $this->output,
            (bool) $this->option('fail-fast'),
            $workers,
            $workerCommandLine,
            $cachedFiles,
            $cacheDir
        );
        $errors = $warmer->warmUp();

        if ($errors > 0) {
            $this->error(sprintf('Go! AOP proxy cache warmup failed: %d file(s) could not be woven.', $errors));

            return self::FAILURE;
        }

        $this->info('Go! AOP proxy cache has been warmed up.');

        return self::SUCCESS;
    }

    /**
     * Returns the command line that starts one warmup worker process, or an
     * empty list when none can be started from here (the files are then
     * woven in this process).
     *
     * A worker is the application's own "artisan" running the hidden
     * "aop:warmup-worker" command, so it boots with the same environment,
     * configuration and aspects as this process.
     *
     * @return list<string>
     */
    protected function workerCommandLine(int $cachedFiles, ?string $cacheDir): array
    {
        $artisan = $this->laravel->basePath('artisan');
        if (!is_file($artisan) || !function_exists('proc_open')) {
            return [];
        }

        $commandLine = [
            PHP_BINARY,
            '-d',
            'memory_limit=' . ini_get('memory_limit'),
            // A fatal error must not end up in the message stream of the worker
            '-d',
            'display_errors=stderr',
            $artisan,
            WarmupWorkerCommand::NAME,
            '--cached-files=' . $cachedFiles,
        ];
        if ($cacheDir !== null) {
            $commandLine[] = '--cache-dir=' . $cacheDir;
        }
        $environment = $this->input->hasOption('env') ? $this->option('env') : null;
        if (is_string($environment) && $environment !== '') {
            $commandLine[] = '--env=' . $environment;
        }

        return $commandLine;
    }

    /**
     * Reads an option that must hold a positive integer
     */
    private function positiveIntegerOption(string $name): int
    {
        $value = $this->option($name);
        if (is_int($value) && $value > 0) {
            return $value;
        }
        if (!is_string($value) || !ctype_digit($value) || (int) $value < 1) {
            throw InvalidConfigurationException::optionNotPositiveInteger($name, is_string($value) ? $value : get_debug_type($value));
        }

        return (int) $value;
    }
}
