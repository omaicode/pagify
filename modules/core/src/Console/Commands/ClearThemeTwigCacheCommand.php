<?php

namespace Pagify\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearThemeTwigCacheCommand extends Command
{
    protected $signature = 'cms:theme:clear-cache {--path= : Override Twig cache path}';

    protected $description = 'Clear and recreate Twig cache used by frontend theme render engine.';

    public function handle(): int
    {
        $path = trim((string) ($this->option('path') ?: config('core.frontend_ui.render.cache.path', storage_path('framework/cache/twig'))));

        if ($path === '') {
            $this->error('Twig cache path is empty.');

            return self::INVALID;
        }

        $normalized = rtrim($path, DIRECTORY_SEPARATOR);

        if (! is_dir($normalized)) {
            File::ensureDirectoryExists($normalized);
            $this->info(sprintf('Twig cache path did not exist. Created: %s', $normalized));

            return self::SUCCESS;
        }

        $fileCount = count(File::allFiles($normalized));
        $dirCount = count(File::directories($normalized));

        File::deleteDirectory($normalized);
        File::ensureDirectoryExists($normalized);

        $this->info(sprintf(
            'Twig cache cleared: %s (removed %d files, %d directories).',
            $normalized,
            $fileCount,
            $dirCount
        ));

        return self::SUCCESS;
    }
}
