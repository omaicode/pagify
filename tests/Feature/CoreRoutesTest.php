<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\Admin;
use Tests\TestCase;

class CoreRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_route_is_accessible(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }

    public function test_admin_login_page_is_accessible_for_guests(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
    }

    public function test_admin_dashboard_redirects_guest_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_api_tokens_page_redirects_guest_to_login(): void
    {
        $response = $this->get('/admin/api-tokens');

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_modules_page_redirects_guest_to_login(): void
    {
        $response = $this->get('/admin/modules');

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_modules_api_redirects_guest_to_login(): void
    {
        $response = $this->get('/api/v1/admin/modules');

        $response->assertRedirect('/admin/login');
    }

    public function test_admin_api_tokens_page_returns_403_for_authenticated_user_without_permission(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'web');

        $response = $this->get('/admin/api-tokens');

        $response->assertForbidden();
    }

    public function test_admin_tokens_api_returns_403_for_authenticated_user_without_permission(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'web');

        $response = $this->get('/api/v1/admin/tokens');

        $response->assertForbidden();
    }

    public function test_admin_modules_page_returns_403_for_authenticated_user_without_permission(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'web');

        $response = $this->get('/admin/modules');

        $response->assertForbidden();
    }

    public function test_admin_modules_api_returns_403_for_authenticated_user_without_permission(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'web');

        $response = $this->get('/api/v1/admin/modules');

        $response->assertForbidden();
    }

    public function test_admin_tokens_api_redirects_guest_to_login(): void
    {
        $response = $this->get('/api/v1/admin/tokens');

        $response->assertRedirect('/admin/login');
    }
}
