<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Console;

use Go\Console\Command\OpcacheWarmupCommand as FrameworkOpcacheWarmupCommand;
use Go\Core\AspectKernel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Primes the opcache file cache with the AOP cache and the application, the
 * "cache:warmup:opcache" command of the framework run on the kernel of the
 * application.
 *
 * The framework command boots the kernel by executing a "loader" file given
 * as an argument; artisan boots the application, and with it the kernel,
 * before the command runs, so the kernel is injected instead and the argument
 * is dropped. Everything else is the framework command: the checks of the
 * opcache settings, the files it compiles and its options.
 */
#[AsCommand(
    name: self::NAME,
    description: 'Prime the opcache file cache with the Go! AOP cache and the application',
    help: <<<'EOT'
Compiles into the opcache file cache (<info>opcache.file_cache</info>) what a
production request loads: the AOP cache (woven classes, proxies, advisor
caches and metadata), Composer's class map and autoloaded files, the sources
below the include paths that are not woven and the entry points given with
<info>--entry</info>. A new PHP-FPM master or CLI process then loads compiled scripts
from disk instead of compiling them.

Compiled scripts are written to disk only by a process started with the file
cache settings, which can not be changed at runtime:

  <info>mkdir -p /var/cache/opcache && php -d opcache.enable_cli=1 -d opcache.file_cache_only=1 \
      -d opcache.file_cache=/var/cache/opcache \
      artisan %command.name% --file-cache=/var/cache/opcache --entry=public/index.php --entry=artisan</info>

<info>--file-cache=DIR</info> checks that the process writes to that directory; without
it the configured directory is used. The file cache belongs to the PHP build and
to the absolute paths of the files: run the command in the final image, after
<info>aop:warmup</info>, with the PHP binary that serves the requests.

The AOP cache and the entry points must compile; files of the Composer class
map that do not (vendor test stubs written for a newer PHP) are listed and
skipped. <info>-v</info> lists every file.
EOT,
)]
class OpcacheWarmupCommand extends FrameworkOpcacheWarmupCommand
{
    public const string NAME = 'aop:warmup-opcache';

    public function __construct(AspectKernel $aspectKernel)
    {
        $this->aspectKernel = $aspectKernel;

        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();

        $definition = $this->getDefinition();
        $definition->setArguments(array_values(array_filter(
            $definition->getArguments(),
            static fn (InputArgument $argument): bool => $argument->getName() !== 'loader'
        )));
    }

    /**
     * The kernel of the application is injected, there is nothing to load
     */
    protected function loadAspectKernel(InputInterface $input, OutputInterface $output): void
    {
    }
}
