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
use Go\Instrument\ClassLoading\CacheWarmer;
use Illuminate\Console\Command;

/**
 * Warms up the Go! AOP proxy cache ahead of time (e.g. during deployment),
 * so no weaving happens on the first real request.
 *
 * The command fails when a file cannot be woven, so a deploy script stops
 * instead of shipping an incomplete cache. Weaving errors are written to
 * the error output.
 */
class WarmupCommand extends Command
{
    protected $signature = 'aop:warmup
                            {--fail-fast : Stop after the first file that fails to weave}';

    protected $description = 'Warm up the Go! AOP proxy cache for all weavable classes';

    public function handle(AspectKernel $aspectKernel): int
    {
        $warmer = new CacheWarmer($aspectKernel, $this->output, (bool) $this->option('fail-fast'));
        $errors = $warmer->warmUp();

        if ($errors > 0) {
            $this->error(sprintf('Go! AOP proxy cache warmup failed: %d file(s) could not be woven.', $errors));

            return self::FAILURE;
        }

        $this->info('Go! AOP proxy cache has been warmed up.');

        return self::SUCCESS;
    }
}
