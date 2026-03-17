<?php

namespace Pagify\Core\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CoreMakePluginCommandTest extends TestCase
{
    private string $pluginRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginRoot = base_path('plugins/demo-plugin');
    }

    protected function tearDown(): void
    {
        if (is_dir($this->pluginRoot)) {
            File::deleteDirectory($this->pluginRoot);
        }

        parent::tearDown();
    }

    public function test_cms_make_plugin_generates_standard_skeleton(): void
    {
        $exitCode = Artisan::call('cms:make-plugin', [
            'name' => 'Demo Plugin',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->pluginRoot.'/plugin.json');
        $this->assertFileExists($this->pluginRoot.'/src/Providers/PluginServiceProvider.php');
        $this->assertFileExists($this->pluginRoot.'/README.md');
    }
}
