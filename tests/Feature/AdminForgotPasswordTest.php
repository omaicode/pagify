<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Pagify\Core\Models\Admin;
use Pagify\Core\Notifications\AdminResetPasswordNotification;
use Tests\TestCase;

class AdminForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_is_accessible_for_guest_admin(): void
    {
        $this->get('/admin/forgot-password')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Auth/ForgotPassword')
                ->has('submitAction')
                ->has('loginUrl'));
    }

    public function test_admin_can_request_reset_password_link_by_email(): void
    {
        Notification::fake();

        /** @var Admin $admin */
        $admin = Admin::factory()->create([
            'email' => 'owner@example.test',
        ]);

        $this->post('/admin/forgot-password', [
            'email' => $admin->email,
        ])->assertRedirect();

        Notification::assertSentTo($admin, AdminResetPasswordNotification::class);
    }

    public function test_admin_can_reset_password_with_valid_token(): void
    {
        Notification::fake();

        /** @var Admin $admin */
        $admin = Admin::factory()->create([
            'email' => 'owner@example.test',
            'password' => Hash::make('old-password-123'),
        ]);

        $this->post('/admin/forgot-password', [
            'email' => $admin->email,
        ])->assertRedirect();

        $sentNotification = Notification::sent($admin, AdminResetPasswordNotification::class)->first();
        $this->assertNotNull($sentNotification);

        /** @var AdminResetPasswordNotification $resetNotification */
        $resetNotification = $sentNotification;
        $token = $resetNotification->token;

        $this->post('/admin/reset-password', [
            'token' => $token,
            'email' => $admin->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect('/admin/login');

        $admin->refresh();
        $this->assertTrue(Hash::check('new-password-123', $admin->password));

        $this->post('/admin/login', [
            'username' => $admin->email,
            'password' => 'new-password-123',
        ])->assertRedirect('/admin');
    }
}
