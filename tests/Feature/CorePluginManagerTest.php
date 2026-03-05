<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Pagify\Core\Models\Admin;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CorePluginManagerTest extends TestCase
{
    use RefreshDatabase;

    private string $pluginRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pluginRoot = base_path('plugins/test-plugin-manager');
        File::ensureDirectoryExists($this->pluginRoot);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->pluginRoot)) {
            File::deleteDirectory($this->pluginRoot);
        }

        parent::tearDown();
    }

    public function test_admin_can_list_plugins_from_manifest_directory(): void
    {
        file_put_contents(
            $this->pluginRoot.'/plugin.json',
            json_encode([
                'slug' => 'test-plugin-manager',
                'name' => 'Test Plugin Manager',
                'version' => '0.1.0',
                'requires' => [
                    'php' => '>=8.2',
                    'laravel' => '^12.0',
                ],
                'extension_points' => [
                    'field_types' => ['color'],
                    'blocks' => [],
                    'dashboard_widgets' => [],
                    'automation_actions' => [],
                    'menu_items' => [],
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        /** @var Admin $admin */
        $admin = Admin::factory()->create();
        Permission::findOrCreate('core.module.manage', 'web');
        $admin->givePermissionTo('core.module.manage');

        $this->actingAs($admin, 'web')
            ->getJson('/api/v1/admin/plugins')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'slug' => 'test-plugin-manager',
            ]);
    }

    public function test_enabling_incompatible_plugin_returns_validation_error(): void
    {
        file_put_contents(
            $this->pluginRoot.'/plugin.json',
            json_encode([
                'slug' => 'test-plugin-manager',
                'name' => 'Test Plugin Manager',
                'version' => '0.1.0',
                'requires' => [
                    'php' => '>=99.0',
                ],
                'extension_points' => [
                    'field_types' => [],
                    'blocks' => [],
                    'dashboard_widgets' => [],
                    'automation_actions' => [],
                    'menu_items' => [],
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        /** @var Admin $admin */
        $admin = Admin::factory()->create();
        Permission::findOrCreate('core.module.manage', 'web');
        $admin->givePermissionTo('core.module.manage');

        $this->actingAs($admin, 'web')
            ->patchJson('/api/v1/admin/plugins/test-plugin-manager', [
                'enabled' => true,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'PLUGIN_STATE_UPDATE_FAILED');
    }
}
