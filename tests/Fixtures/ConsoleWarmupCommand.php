<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests\Fixtures;

use Go\Laravel\GoAopBridge\Console\WarmupCommand;

/**
 * Warmup command of tests/Fixtures/bin/console.php: its workers run that
 * console instead of the "artisan" of the testbench skeleton, with the
 * command line of the bridge otherwise (aop:warmup-worker and its options).
 */
class ConsoleWarmupCommand extends WarmupCommand
{
    protected function workerCommandLine(int $cachedFiles, ?string $cacheDir): array
    {
        $artisan = $this->laravel->basePath('artisan');

        return array_map(
            static fn (string $argument): string => $argument === $artisan ? __DIR__ . '/bin/console.php' : $argument,
            parent::workerCommandLine($cachedFiles, $cacheDir)
        );
    }
}
