<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Pagify\Core\Models\Admin;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UpdaterPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_updater_page_redirects_guest_to_login(): void
    {
        $this->get('/admin/updater')->assertRedirect('/admin/login');
    }

    public function test_updater_page_returns_403_without_permission(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'web')
            ->get('/admin/updater')
            ->assertForbidden();
    }

    public function test_updater_page_renders_with_api_routes_for_authorized_admin(): void
    {
        $admin = $this->makeAdminWithUpdaterPermission();

        $this->actingAs($admin, 'web')
            ->get('/admin/updater')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Updater/Index')
                ->has('apiRoutes.index')
                ->has('apiRoutes.showBase')
                ->has('apiRoutes.dryRun')
                ->has('apiRoutes.updateModuleBase')
                ->has('apiRoutes.updateAll')
                ->has('apiRoutes.rollbackBase')
                ->where('breadcrumbs.0.label_key', 'dashboard')
                ->where('breadcrumbs.1.label_key', 'settings')
                ->where('breadcrumbs.2.label_key', 'settings_item_updater'));
    }

    public function test_settings_page_shows_updater_item_in_system_group_for_authorized_admin(): void
    {
        $admin = $this->makeAdminWithUpdaterPermission();

        $this->actingAs($admin, 'web')
            ->get('/admin/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings/Index')
                ->where('groups', function ($groups): bool {
                    $normalizedGroups = $groups instanceof \Illuminate\Support\Collection
                        ? $groups->all()
                        : (is_array($groups) ? $groups : []);

                    foreach ($normalizedGroups as $group) {
                        if (($group['key'] ?? null) !== 'system') {
                            continue;
                        }

                        $items = $group['items'] ?? [];

                        foreach ($items as $item) {
                            if (($item['label'] ?? null) !== 'Updater') {
                                continue;
                            }

                            return str_ends_with((string) ($item['href'] ?? ''), '/admin/updater');
                        }
                    }

                    return false;
                }));
    }

    private function makeAdminWithUpdaterPermission(): Admin
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $permission = Permission::query()->firstOrCreate([
            'name' => 'core.updater.manage',
            'guard_name' => 'web',
        ]);

        $admin->givePermissionTo($permission);

        return $admin;
    }
}
