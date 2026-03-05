<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Pagify\Core\Models\Admin;
use Pagify\Core\Services\ModuleStateService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ContentModuleBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_admin_dashboard_redirects_guest_to_login(): void
    {
        $response = $this->get('/admin/content');

        $response->assertRedirect('/admin/login');
    }

    public function test_content_admin_dashboard_is_accessible_for_authenticated_admin(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'web')->get('/admin/content');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Content/Dashboard')
            ->has('title')
            ->has('description')
            ->has('stats')
            ->has('routes')
        );
    }

    public function test_content_admin_legacy_route_aliases_are_removed(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'web')
            ->get('/admin/content/dashboard')
            ->assertNotFound();

        $this->actingAs($admin, 'web')
            ->get('/admin/content/content-types')
            ->assertNotFound();

        $this->actingAs($admin, 'web')
            ->get('/admin/content/content-types/create')
            ->assertNotFound();
    }

    public function test_content_admin_health_api_redirects_guest_to_login(): void
    {
        $response = $this->get('/api/v1/admin/content/health');

        $response->assertRedirect('/admin/login');
    }

    public function test_content_admin_routes_are_blocked_when_module_is_disabled(): void
    {
        /** @var ModuleStateService $stateService */
        $stateService = app(ModuleStateService::class);
        $stateService->setEnabled('content', false);

        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'web')
            ->get('/admin/content')
            ->assertNotFound();

        $this->actingAs($admin, 'web')
            ->get('/api/v1/admin/content/health')
            ->assertNotFound()
            ->assertJsonPath('code', 'MODULE_DISABLED');
    }

    public function test_settings_hides_content_link_when_content_module_is_disabled(): void
    {
        /** @var ModuleStateService $stateService */
        $stateService = app(ModuleStateService::class);
        $stateService->setEnabled('content', false);

        /** @var Admin $admin */
        $admin = Admin::factory()->create();
        Permission::findOrCreate('content.type.viewAny', 'web');
        $admin->givePermissionTo('content.type.viewAny');

        $response = $this->actingAs($admin, 'web')->get('/admin/settings');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Settings/Index')
            ->where('groups', static function ($groups): bool {
                $labels = collect($groups)
                    ->flatMap(static fn (array $group): array => $group['items'] ?? [])
                    ->pluck('label')
                    ->all();

                return ! in_array('Content', $labels, true);
            })
        );
    }
}
