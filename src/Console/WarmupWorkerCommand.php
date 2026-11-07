<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Console;

use Go\Core\AspectKernel;
use Go\Instrument\ClassLoading\Warmup\WarmupWorker;
use Illuminate\Console\Command;

/**
 * Worker process of a parallel "aop:warmup".
 *
 * Started by the warmup command, never by hand: it weaves the files the
 * coordinating process sends to its standard input and answers each of them
 * on its standard output with the outcome and the cache records it
 * produced. Booting the application has already initialized the AOP kernel
 * of this process, with the same configuration and aspects as the
 * coordinator, so the worker loop runs on that kernel.
 */
class WarmupWorkerCommand extends Command
{
    public const string NAME = 'aop:warmup-worker';

    protected $signature = self::NAME . '
                            {--cached-files= : Number of parsed files kept in memory (default: the "parsedFilesLimit" option)}
                            {--cache-dir= : Build the cache in this directory instead of the one of the kernel}';

    protected $description = 'Worker process of "aop:warmup", started by that command';

    protected $hidden = true;

    public function handle(AspectKernel $aspectKernel): int
    {
        $cachedFiles = $this->option('cached-files');
        $cachedFiles = is_string($cachedFiles) && ctype_digit($cachedFiles) && (int) $cachedFiles > 0
            ? (int) $cachedFiles
            : $aspectKernel->getOptions()['parsedFilesLimit'];
        $cacheDir    = $this->option('cache-dir');

        // The worker boots its kernel by executing a loader file. The kernel of
        // this process was initialized by the application already, so the file
        // of this class, which is included for sure, is handed over: including
        // it again is a no-op, and the initialized kernel is picked up.
        return WarmupWorker::main(
            __FILE__,
            $cachedFiles,
            null,
            null,
            is_string($cacheDir) && $cacheDir !== '' ? $cacheDir : null
        );
    }
}
