<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CoreMakeThemeCommandTest extends TestCase
{
    private string $themeRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themeRoot = base_path('themes/main/demo-theme');
    }

    protected function tearDown(): void
    {
        if (is_dir($this->themeRoot)) {
            File::deleteDirectory($this->themeRoot);
        }

        parent::tearDown();
    }

    public function test_cms_make_theme_generates_standard_skeleton(): void
    {
        $exitCode = Artisan::call('cms:make-theme', [
            'name' => 'Demo Theme',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->themeRoot.'/theme.json');
        $this->assertFileExists($this->themeRoot.'/layouts/app.twig');
        $this->assertFileExists($this->themeRoot.'/pages/home.twig');
        $this->assertFileExists($this->themeRoot.'/README.md');
    }
}
