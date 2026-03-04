<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Core\Models\Admin;
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
}
