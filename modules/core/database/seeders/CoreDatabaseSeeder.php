<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Models\Admin;
use Modules\Core\Models\Site;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CoreDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::query()->firstOrCreate(
            ['slug' => 'default'],
            [
                'name' => 'Default Site',
                'domain' => 'localhost',
                'locale' => 'en',
                'is_active' => true,
            ]
        );

        $permissions = [
            'core.site.viewAny',
            'core.site.view',
            'core.site.create',
            'core.site.update',
            'core.site.delete',
            'core.setting.viewAny',
            'core.setting.view',
            'core.setting.create',
            'core.setting.update',
            'core.audit.viewAny',
            'core.audit.view',
            'core.token.manage',
            'core.module.manage',
            'content.type.viewAny',
            'content.type.create',
            'content.type.update',
            'content.type.delete',
            'content.entry.viewAny',
            'content.entry.view',
            'content.entry.create',
            'content.entry.update',
            'content.entry.delete',
            'content.entry.publish',
            'content.relation.view',
            'content.entry.revision.view',
            'content.entry.revision.rollback',
            'content.api.read',
        ];

        foreach ($permissions as $permissionName) {
            Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $roles = [
            'admin' => $permissions,
            'editor' => [
                'core.site.view',
                'core.setting.viewAny',
                'core.setting.view',
                'core.setting.update',
                'core.audit.view',
            ],
            'author' => [
                'core.site.view',
                'core.setting.view',
            ],
            'viewer' => [
                'core.site.view',
                'core.setting.view',
                'core.audit.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($rolePermissions);
        }

        $admin = Admin::query()->firstOrCreate(
            ['username' => 'admin'],
            [
                'site_id' => $site->id,
                'name' => 'System Admin',
                'email' => 'admin@localhost',
                'locale' => 'en',
                'password' => Hash::make('password'),
            ]
        );

        $admin->assignRole('admin');
    }
}
