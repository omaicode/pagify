<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Pagify\Core\Models\Admin;
use Pagify\Media\Models\MediaAsset;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_redirects_guest_to_login(): void
    {
        $this->get('/admin/profile')->assertRedirect('/admin/login');
    }

    public function test_profile_page_is_accessible_for_authenticated_admin(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create([
            'name' => 'Profile Admin',
            'nickname' => 'Boss',
            'bio' => 'About me',
        ]);

        $this->actingAs($admin, 'web')
            ->get('/admin/profile')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Profile/Index')
                ->where('profile.name', 'Profile Admin')
                ->where('profile.nickname', 'Boss')
                ->where('profile.bio', 'About me')
                ->where('breadcrumbs.0.label_key', 'dashboard')
                ->where('breadcrumbs.1.label_key', 'profile')
                ->has('apiRoutes.updateProfile')
                ->has('apiRoutes.updatePassword')
                ->has('apiRoutes.uploadAvatar')
                ->has('apiRoutes.removeAvatar'));
    }

    public function test_admin_can_update_profile_fields(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'web')
            ->patchJson('/api/v1/admin/profile', [
                'name' => 'Updated Name',
                'nickname' => 'Captain',
                'bio' => 'A short bio',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.admin.name', 'Updated Name')
            ->assertJsonPath('data.admin.nickname', 'Captain')
            ->assertJsonPath('data.admin.bio', 'A short bio');

        $this->assertDatabaseHas('admins', [
            'id' => $admin->id,
            'name' => 'Updated Name',
            'nickname' => 'Captain',
            'bio' => 'A short bio',
        ]);
    }

    public function test_admin_can_change_password_with_current_password(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->actingAs($admin, 'web')
            ->putJson('/api/v1/admin/profile/password', [
                'current_password' => 'old-password',
                'new_password' => 'new-password-123',
                'new_password_confirmation' => 'new-password-123',
            ]);

        $response->assertOk()->assertJsonPath('success', true);

        $admin->refresh();
        $this->assertTrue(Hash::check('new-password-123', $admin->password));
    }

    public function test_admin_can_upload_and_remove_avatar(): void
    {
        Storage::fake('public');

        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $uploadResponse = $this->actingAs($admin, 'web')
            ->post('/api/v1/admin/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('avatar.png', 128, 128),
            ]);

        $uploadResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $admin->refresh();

        $this->assertNotNull($admin->avatar_path);
        Storage::disk('public')->assertExists((string) $admin->avatar_path);
        $this->assertDatabaseHas('media_assets', [
            'path' => $admin->avatar_path,
            'uploaded_by_admin_id' => $admin->id,
            'kind' => 'image',
        ]);

        $removeResponse = $this->actingAs($admin, 'web')
            ->deleteJson('/api/v1/admin/profile/avatar');

        $removeResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.admin.avatar_url', null);

        $admin->refresh();
        $this->assertNull($admin->avatar_path);
        $this->assertSame(0, MediaAsset::query()->where('uploaded_by_admin_id', $admin->id)->count());
    }
}
