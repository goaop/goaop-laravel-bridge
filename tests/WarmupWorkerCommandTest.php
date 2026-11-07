<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Tests;

use Go\Laravel\GoAopBridge\Console\OpcacheWarmupCommand;
use Go\Laravel\GoAopBridge\Console\WarmupCommand;
use Go\Laravel\GoAopBridge\Console\WarmupWorkerCommand;
use Illuminate\Contracts\Console\Kernel;

class WarmupWorkerCommandTest extends TestCase
{
    public function testWorkerCommandIsRegisteredButHidden(): void
    {
        $commands = $this->app->make(Kernel::class)->all();

        $this->assertArrayHasKey('aop:warmup', $commands);
        $this->assertInstanceOf(WarmupCommand::class, $commands['aop:warmup']);
        $this->assertFalse($commands['aop:warmup']->isHidden());

        $this->assertArrayHasKey(WarmupWorkerCommand::NAME, $commands);
        $this->assertInstanceOf(WarmupWorkerCommand::class, $commands[WarmupWorkerCommand::NAME]);
        $this->assertTrue($commands[WarmupWorkerCommand::NAME]->isHidden(), 'The worker is started by aop:warmup, never by hand');
    }

    public function testOpcacheWarmupCommandRunsOnTheKernelOfTheApplication(): void
    {
        $commands = $this->app->make(Kernel::class)->all();

        $this->assertArrayHasKey(OpcacheWarmupCommand::NAME, $commands);
        $command = $commands[OpcacheWarmupCommand::NAME];
        $this->assertInstanceOf(OpcacheWarmupCommand::class, $command);
        $this->assertFalse($command->getDefinition()->hasArgument('loader'), 'The kernel is the one of the application');
        $this->assertTrue($command->getDefinition()->hasOption('file-cache'));
        $this->assertTrue($command->getDefinition()->hasOption('entry'));
        $this->assertStringContainsString('artisan aop:warmup-opcache', $command->getProcessedHelp());
    }

    public function testWarmupCommandDeclaresTheParallelOptions(): void
    {
        $definition = $this->app->make(Kernel::class)->all()['aop:warmup']->getDefinition();

        $this->assertTrue($definition->hasOption('workers'));
        $this->assertSame('1', $definition->getOption('workers')->getDefault());
        $this->assertTrue($definition->hasOption('cached-files'));
        $this->assertTrue($definition->hasOption('cache-dir'));
        $this->assertTrue($definition->hasOption('fail-fast'));
        $this->assertTrue($definition->hasOption('preload'));
        $this->assertTrue($definition->getOption('preload')->isValueOptional());
        $this->assertFalse($definition->getOption('preload')->getDefault(), 'Tells --preload left out from --preload without a value');
    }
}
