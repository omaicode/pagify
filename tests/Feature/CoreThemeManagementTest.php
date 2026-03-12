<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Pagify\Core\Models\Admin;
use Pagify\Core\Models\Site;
use Pagify\Core\Models\Setting;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CoreThemeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        config()->set('core.frontend_ui.themes_base_path', 'storage/testing/themes/main');
        config()->set('core.frontend_ui.theme', 'default');
        config()->set('core.frontend_ui.fallback_theme', 'default');

        File::deleteDirectory(base_path('storage/testing/themes/main'));
        File::ensureDirectoryExists(base_path('storage/testing/themes/main/default'));
        File::put(base_path('storage/testing/themes/main/default/theme.json'), json_encode([
            'slug' => 'default',
            'name' => 'Default Main Theme',
            'version' => '1.0.0',
        ], JSON_PRETTY_PRINT));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path('storage/testing/themes/main'));

        parent::tearDown();
    }

    public function test_theme_routes_are_forbidden_without_permission(): void
    {
        $admin = Admin::factory()->create();

        $this->createThemeFixture('marketing', [
            'name' => 'Marketing Theme',
        ]);

        $this->actingAs($admin, 'web')
            ->getJson('/api/v1/admin/themes')
            ->assertForbidden();

        $this->actingAs($admin, 'web')
            ->patchJson('/api/v1/admin/themes/marketing', [
                'name' => 'Marketing Updated',
            ])
            ->assertForbidden();

        $this->actingAs($admin, 'web')
            ->putJson('/api/v1/admin/themes/marketing/activate')
            ->assertForbidden();

        $this->actingAs($admin, 'web')
            ->deleteJson('/api/v1/admin/themes/marketing')
            ->assertForbidden();
    }

    public function test_theme_management_crud_and_activate_flow(): void
    {
        $admin = $this->makeAdminWithPermissions(['core.theme.manage']);
        $site = Site::factory()->create();

        $this->createThemeFixture('marketing', [
            'name' => 'Marketing Theme',
            'version' => '1.2.0',
            'description' => 'Landing pages',
            'author' => 'Team',
        ]);

        $this->assertTrue(File::exists(base_path('storage/testing/themes/main/marketing/theme.json')));

        $this->actingAs($admin, 'web')
            ->patchJson('/api/v1/admin/themes/marketing', [
                'name' => 'Marketing Theme Updated',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Marketing Theme Updated');

        $this->actingAs($admin, 'web')
            ->putJson('/api/v1/admin/themes/marketing/activate', [
                'site_id' => $site->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.site_id', $site->id);

        $this->assertDatabaseHas('settings', [
            'site_id' => $site->id,
            'key' => 'theme.main.active',
        ]);

        $this->actingAs($admin, 'web')
            ->deleteJson('/api/v1/admin/themes/marketing')
            ->assertStatus(409)
            ->assertJsonPath('code', 'THEME_IN_USE');

        Setting::query()
            ->where('site_id', $site->id)
            ->where('key', 'theme.main.active')
            ->delete();

        $this->actingAs($admin, 'web')
            ->deleteJson('/api/v1/admin/themes/marketing')
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertFalse(File::exists(base_path('storage/testing/themes/main/marketing')));
    }

    public function test_default_theme_is_locked_for_deletion(): void
    {
        $admin = $this->makeAdminWithPermissions(['core.theme.manage']);

        $this->actingAs($admin, 'web')
            ->deleteJson('/api/v1/admin/themes/default')
            ->assertStatus(422)
            ->assertJsonPath('code', 'THEME_LOCKED');
    }

    /**
     * @param array<int, string> $permissions
     */
    private function makeAdminWithPermissions(array $permissions): Admin
    {
        $admin = Admin::factory()->create();

        foreach ($permissions as $permissionName) {
            $permission = Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            $admin->givePermissionTo($permission);
        }

        return $admin;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createThemeFixture(string $slug, array $overrides = []): void
    {
        $themePath = base_path("storage/testing/themes/main/{$slug}");
        File::ensureDirectoryExists($themePath);

        $manifest = array_merge([
            'slug' => $slug,
            'name' => ucfirst($slug),
            'version' => '1.0.0',
            'description' => '',
            'author' => '',
        ], $overrides);

        File::put("{$themePath}/theme.json", json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
