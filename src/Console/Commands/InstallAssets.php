<?php

namespace FluxErp\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Process\Process;

class InstallAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flux:install-assets
        {directory? : The directory to install the assets}
        {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $target = $this->argument('directory');
        if ($target && ! file_exists($target)) {
            throw new \InvalidArgumentException('The target directory does not exist.');
        }

        $this->callSilent('storage:link');

        // require npm packages
        $this->info('Installing npm packages...');

        static::copyStubs(force: $this->option('force'));

        $this->updateNodePackages(function ($packages) {
            return data_get(
                json_decode(
                    file_get_contents(__DIR__ . '/../../../stubs/tailwind/package.json'),
                    true
                ),
                'devDependencies'
            ) + $packages;
        });

        if (file_exists(resource_path('views/welcome.blade.php'))) {
            unlink(resource_path('views/welcome.blade.php'));
        }

        $this->runCommands(['npm install', 'npm run build']);
    }

    protected static function updateNodePackages(callable $callback, $dev = true): void
    {
        if (! file_exists(base_path('package.json'))) {
            return;
        }

        $configurationKey = $dev ? 'devDependencies' : 'dependencies';

        $packages = json_decode(file_get_contents(base_path('package.json')), true);

        $packages[$configurationKey] = $callback(
            array_key_exists($configurationKey, $packages) ? $packages[$configurationKey] : [],
            $configurationKey
        );

        ksort($packages[$configurationKey]);

        file_put_contents(
            base_path('package.json'),
            json_encode($packages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL
        );
    }

    protected function runCommands($commands): void
    {
        $process = Process::fromShellCommandline(implode(' && ', $commands), null, null, null, 180);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $this->output->writeln('  <bg=yellow;fg=black> WARN </> ' . $e->getMessage() . PHP_EOL);
            }
        }

        $process->run(function ($type, $line) {
            $this->output->write('    ' . $line);
        });
    }

    public static function copyStubs(?array $files = null, bool $force = false, ?\Closure $basePath = null): void
    {
        $files = is_array($files)
            ? $files
            : [
                'tailwind.config.js',
                'postcss.config.js',
                'vite.config.js',
            ];

        if (! $basePath) {
            $basePath = fn ($path = '') => base_path($path);
        }

        foreach ($files as $file) {
            if (file_exists($basePath('tailwind.config.js')) && ! $force) {
                continue;
            }

            copy(__DIR__ . '/../../../stubs/tailwind/' . $file, $basePath($file));
            $content = file_get_contents($basePath($file));
            $content = str_replace(
                '{{ relative_path }}',
                substr(realpath(__DIR__ . '/../../../'), strlen($basePath())),
                $content
            );
            file_put_contents($basePath($file), $content);
        }
    }
}
