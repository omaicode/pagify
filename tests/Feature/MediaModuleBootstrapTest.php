<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Pagify\Core\Models\Admin;
use Pagify\Core\Services\ModuleStateService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MediaModuleBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_library_redirects_guest_to_login(): void
    {
        $this->get('/admin/media')->assertRedirect('/admin/login');
    }

    public function test_media_library_is_accessible_for_authenticated_admin_with_permission(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();
        Permission::findOrCreate('media.asset.viewAny', 'web');
        $admin->givePermissionTo('media.asset.viewAny');

        $response = $this->actingAs($admin, 'web')->get('/admin/media');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Media/Library/Index')
            ->has('apiRoutes')
            ->where('breadcrumbs.0.label_key', 'dashboard')
            ->where('breadcrumbs.1.label_key', 'media_nav_library')
            ->where('defaultViewMode', 'grid')
        );
    }

    public function test_media_admin_api_assets_returns_forbidden_without_permission(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'web')
            ->get('/api/v1/admin/media/assets')
            ->assertForbidden();
    }

    public function test_media_admin_routes_are_blocked_when_module_is_disabled(): void
    {
        /** @var ModuleStateService $stateService */
        $stateService = app(ModuleStateService::class);
        $stateService->setEnabled('media', false);

        /** @var Admin $admin */
        $admin = Admin::factory()->create();
        Permission::findOrCreate('media.asset.viewAny', 'web');
        $admin->givePermissionTo('media.asset.viewAny');

        $this->actingAs($admin, 'web')
            ->get('/admin/media')
            ->assertNotFound();

        $this->actingAs($admin, 'web')
            ->get('/api/v1/admin/media/assets')
            ->assertNotFound()
            ->assertJsonPath('code', 'MODULE_DISABLED');
    }
}
