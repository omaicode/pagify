<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pagify\Core\Models\Admin;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CoreAdminE2ETest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_switch_persists_for_authenticated_admin(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create([
            'locale' => 'en',
        ]);

        $this->actingAs($admin, 'web');

        $response = $this->post('/admin/locale', [
            'locale' => 'vi',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'vi');

        $admin->refresh();

        $this->assertSame('vi', $admin->locale);
    }

    public function test_admin_token_lifecycle_create_list_revoke(): void
    {
        $permission = Permission::query()->firstOrCreate([
            'name' => 'core.token.manage',
            'guard_name' => 'web',
        ]);

        /** @var Admin $admin */
        $admin = Admin::factory()->create();
        $admin->givePermissionTo($permission);

        $this->actingAs($admin, 'web');

        $createResponse = $this->postJson('/api/v1/admin/tokens', [
            'name' => 'e2e-token',
            'abilities' => ['*'],
        ]);

        $createResponse
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'e2e-token');

        $tokenId = (int) $createResponse->json('data.id');

        $listResponse = $this->getJson('/api/v1/admin/tokens');

        $listResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $tokenId,
                'name' => 'e2e-token',
            ]);

        $deleteResponse = $this->deleteJson('/api/v1/admin/tokens/' . $tokenId);

        $deleteResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.deleted', true);

        $listAfterDeleteResponse = $this->getJson('/api/v1/admin/tokens');

        $this->assertSame([], array_filter(
            $listAfterDeleteResponse->json('data', []),
            static fn (array $token): bool => (int) ($token['id'] ?? 0) === $tokenId,
        ));
    }
}
