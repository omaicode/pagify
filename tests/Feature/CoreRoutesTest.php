<?php

namespace Tests\Feature;

use Tests\TestCase;

class CoreRoutesTest extends TestCase
{
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
}
