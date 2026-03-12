<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CoreThemeTwigCacheClearCommandTest extends TestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cachePath = storage_path('framework/cache/twig-test-clear');
        config()->set('core.frontend_ui.render.cache.path', $this->cachePath);

        File::deleteDirectory($this->cachePath);
        File::ensureDirectoryExists($this->cachePath.'/nested');
        File::put($this->cachePath.'/nested/sample.php', '<?php return [];');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->cachePath);

        parent::tearDown();
    }

    public function test_command_clears_and_recreates_twig_cache_directory(): void
    {
        $this->assertFileExists($this->cachePath.'/nested/sample.php');

        $exitCode = Artisan::call('cms:theme:clear-cache');

        $this->assertSame(0, $exitCode);
        $this->assertDirectoryExists($this->cachePath);
        $this->assertFileDoesNotExist($this->cachePath.'/nested/sample.php');
    }
}
