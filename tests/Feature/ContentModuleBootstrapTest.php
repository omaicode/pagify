<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $response->assertSee('Content module');
    }

    public function test_content_admin_health_api_redirects_guest_to_login(): void
    {
        $response = $this->get('/api/v1/admin/content/health');

        $response->assertRedirect('/admin/login');
    }
}
