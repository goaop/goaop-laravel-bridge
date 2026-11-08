<?php
/*
 * Go! AOP framework
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

declare(strict_types=1);

namespace Go\Laravel\GoAopBridge\Console;

use FilesystemIterator;
use Go\Console\Command\OpcacheWarmupCommand as FrameworkOpcacheWarmupCommand;
use Go\Core\AspectKernel;
use Illuminate\Foundation\Application;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
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
 * is dropped. The files every Laravel request or artisan run includes besides
 * the classes are compiled as entry points: the front controller, artisan,
 * the files below bootstrap/ (the caches written by "php artisan optimize"
 * included, wherever APP_CONFIG_CACHE and friends put them), config/ and
 * routes/. Everything else is the framework command: the checks of the
 * opcache settings, the other files it compiles and its options.
 */
#[AsCommand(
    name: self::NAME,
    description: 'Prime the opcache file cache with the Go! AOP cache and the application',
    help: <<<'EOT'
Compiles into the opcache file cache (<info>opcache.file_cache</info>) what a
production request loads: the AOP cache (woven classes, proxies, advisor
caches and metadata), Composer's class map and autoloaded files, the sources
below the include paths that are not woven, the files of Laravel and the entry
points given with <info>--entry</info>. A new PHP-FPM master or CLI process then loads
compiled scripts from disk instead of compiling them.

The files of Laravel are <info>public/index.php</info>, <info>artisan</info>, the files below
<info>bootstrap/</info>, <info>config/</info> and <info>routes/</info>, and the caches written by
<info>php artisan optimize</info>: run the command after it.

Compiled scripts are written to disk only by a process started with the file
cache settings, which can not be changed at runtime:

  <info>mkdir -p /var/cache/opcache && php -d opcache.enable_cli=1 -d opcache.file_cache_only=1 \
      -d opcache.file_cache=/var/cache/opcache \
      artisan %command.name% --file-cache=/var/cache/opcache</info>

<info>--file-cache=DIR</info> checks that the process writes to that directory; without
it the configured directory is used. The file cache belongs to the PHP build and
to the absolute paths of the files: run the command in the final image, after
<info>aop:warmup</info>, with the PHP binary that serves the requests.

The AOP cache, the files of Laravel and the entry points must compile; files of the Composer class
map that do not (vendor test stubs written for a newer PHP) are listed and
skipped. <info>-v</info> lists every file.
EOT,
)]
class OpcacheWarmupCommand extends FrameworkOpcacheWarmupCommand
{
    public const string NAME = 'aop:warmup-opcache';

    public function __construct(AspectKernel $aspectKernel, private readonly Application $laravel)
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entries = $input->getOption('entry');
        $entries = [...(is_array($entries) ? $entries : []), ...$this->laravelFiles()];
        // A file of Laravel also given with --entry is compiled once; an entry
        // that does not resolve is left to the framework command, which reports it
        $uniqueEntries = [];
        foreach ($entries as $entry) {
            $path = is_string($entry) ? realpath($entry) : false;
            $uniqueEntries[$path === false ? 'unresolved:' . count($uniqueEntries) : $path] = $entry;
        }
        $input->setOption('entry', array_values($uniqueEntries));

        return parent::execute($input, $output);
    }

    /**
     * Returns the files a Laravel request or artisan run includes besides the
     * classes, the ones that exist
     *
     * @return list<string>
     */
    protected function laravelFiles(): array
    {
        $files = [
            $this->laravel->publicPath('index.php'),
            $this->laravel->basePath('artisan'),
            // The caches can be moved out of bootstrap/cache (APP_CONFIG_CACHE and friends)
            $this->laravel->getCachedConfigPath(),
            $this->laravel->getCachedServicesPath(),
            $this->laravel->getCachedPackagesPath(),
            $this->laravel->getCachedRoutesPath(),
            $this->laravel->getCachedEventsPath(),
        ];
        foreach ([$this->laravel->bootstrapPath(), $this->laravel->configPath(), $this->laravel->basePath('routes')] as $directory) {
            $files = [...$files, ...$this->phpFilesBelow($directory)];
        }

        $existingFiles = [];
        foreach ($files as $file) {
            $path = realpath($file);
            if ($path !== false && is_file($path)) {
                $existingFiles[$path] = true;
            }
        }

        return array_keys($existingFiles);
    }

    /**
     * Returns the PHP files below a directory, none when it does not exist
     *
     * @return list<string>
     */
    private function phpFilesBelow(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }
        $files    = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * The kernel of the application is injected, there is nothing to load
     */
    protected function loadAspectKernel(InputInterface $input, OutputInterface $output): void
    {
    }
}
