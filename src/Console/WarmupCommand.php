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
 */
class WarmupCommand extends Command
{
    protected $signature = 'aop:warmup';

    protected $description = 'Warm up the Go! AOP proxy cache for all weavable classes';

    public function handle(AspectKernel $aspectKernel): int
    {
        $warmer = new CacheWarmer($aspectKernel, $this->output);
        $warmer->warmUp();

        $this->info('Go! AOP proxy cache has been warmed up.');

        return self::SUCCESS;
    }
}
