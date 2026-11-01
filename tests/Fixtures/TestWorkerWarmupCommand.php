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
use Go\Laravel\GoAopBridge\Tests\TestCase;

/**
 * Warmup command whose workers boot a testbench application instead of
 * the application's artisan (see tests/Fixtures/bin/warmup-worker.php).
 */
class TestWorkerWarmupCommand extends WarmupCommand
{
    /** @var list<list<string>> Command lines handed out, for the assertions */
    public static array $commandLines = [];

    protected function workerCommandLine(int $cachedFiles, ?string $cacheDir): array
    {
        $commandLine = [PHP_BINARY, __DIR__ . '/bin/warmup-worker.php', TestCase::aopCacheDir(), (string) $cachedFiles];
        if ($cacheDir !== null) {
            $commandLine[] = $cacheDir;
        }
        self::$commandLines[] = $commandLine;

        return $commandLine;
    }
}
