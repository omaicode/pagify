<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pagify\Core\Models\Admin;
use Pagify\Core\Models\Site;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CoreAccessManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_pages_return_403_without_permissions(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'web')
            ->get('/admin/permissions')
            ->assertForbidden();

        $this->actingAs($admin, 'web')
            ->get('/admin/admin-groups')
            ->assertForbidden();

        $this->actingAs($admin, 'web')
            ->get('/admin/admins')
            ->assertForbidden();
    }

    public function test_permission_management_crud_flow(): void
    {
        $admin = $this->makeAdminWithPermissions(['core.permission.manage']);

        $createResponse = $this->actingAs($admin, 'web')
            ->postJson('/api/v1/admin/permissions', [
                'name' => 'core.report.view',
            ]);

        $createResponse
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'core.report.view');

        $permissionId = (int) $createResponse->json('data.id');

        $this->actingAs($admin, 'web')
            ->patchJson('/api/v1/admin/permissions/'.$permissionId, [
                'name' => 'core.report.viewAny',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'core.report.viewAny');

        $this->actingAs($admin, 'web')
            ->deleteJson('/api/v1/admin/permissions/'.$permissionId)
            ->assertOk()
            ->assertJsonPath('data.deleted', true);
    }

    public function test_admin_group_management_crud_flow(): void
    {
        $admin = $this->makeAdminWithPermissions(['core.admin.group.manage', 'core.permission.manage']);

        Permission::findOrCreate('core.site.viewAny', 'web');

        $createResponse = $this->actingAs($admin, 'web')
            ->postJson('/api/v1/admin/admin-groups', [
                'name' => 'site-reviewer',
                'permissions' => ['core.site.viewAny'],
            ]);

        $createResponse
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'site-reviewer');

        $groupId = (int) $createResponse->json('data.id');

        $this->actingAs($admin, 'web')
            ->patchJson('/api/v1/admin/admin-groups/'.$groupId, [
                'name' => 'site-manager',
                'permissions' => ['core.site.viewAny'],
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'site-manager');

        $this->actingAs($admin, 'web')
            ->deleteJson('/api/v1/admin/admin-groups/'.$groupId)
            ->assertOk()
            ->assertJsonPath('data.deleted', true);
    }

    public function test_admin_management_crud_and_site_isolation(): void
    {
        $manager = $this->makeAdminWithPermissions(['core.admin.manage']);

        $group = Role::query()->firstOrCreate([
            'name' => 'support',
            'guard_name' => 'web',
        ]);

        $foreignSite = Site::factory()->create();
        Admin::factory()->create([
            'site_id' => $foreignSite->id,
            'name' => 'Foreign Admin',
        ]);

        $createResponse = $this->actingAs($manager, 'web')
            ->postJson('/api/v1/admin/admins', [
                'name' => 'Support Admin',
                'username' => 'support-admin',
                'email' => 'support-admin@example.test',
                'locale' => 'en',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'roles' => [$group->name],
            ]);

        $createResponse
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.username', 'support-admin');

        $managedAdminId = (int) $createResponse->json('data.id');

        $listResponse = $this->actingAs($manager, 'web')
            ->getJson('/api/v1/admin/admins');

        $listResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonMissing(['name' => 'Foreign Admin']);

        $this->actingAs($manager, 'web')
            ->patchJson('/api/v1/admin/admins/'.$managedAdminId, [
                'name' => 'Support Admin Updated',
                'username' => 'support-admin',
                'email' => 'support-admin@example.test',
                'locale' => 'vi',
                'roles' => [$group->name],
            ])
            ->assertOk()
            ->assertJsonPath('data.locale', 'vi');

        $this->actingAs($manager, 'web')
            ->deleteJson('/api/v1/admin/admins/'.$manager->id)
            ->assertStatus(422)
            ->assertJsonPath('code', 'ADMIN_DELETE_SELF_FORBIDDEN');

        $this->actingAs($manager, 'web')
            ->deleteJson('/api/v1/admin/admins/'.$managedAdminId)
            ->assertOk()
            ->assertJsonPath('data.deleted', true);
    }

    /**
     * @param array<int, string> $permissions
     */
    private function makeAdminWithPermissions(array $permissions): Admin
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        foreach ($permissions as $permissionName) {
            $permission = Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            $admin->givePermissionTo($permission);
        }

        return $admin;
    }
}
