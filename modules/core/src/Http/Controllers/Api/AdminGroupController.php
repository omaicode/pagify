<?php

namespace Pagify\Core\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Pagify\Core\Http\Requests\Admin\StoreAdminGroupRequest;
use Pagify\Core\Http\Requests\Admin\UpdateAdminGroupRequest;
use Pagify\Core\Models\Admin;
use Spatie\Permission\Models\Role;

class AdminGroupController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->authorize('manageAdminGroups', Admin::class);

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name']);

        return $this->success($roles->map(static fn (Role $role): array => [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions->pluck('name')->values()->all(),
        ])->values());
    }

    public function store(StoreAdminGroupRequest $request): JsonResponse
    {
        $this->authorize('manageAdminGroups', Admin::class);

        $payload = $request->validated();

        $role = Role::query()->create([
            'name' => (string) $payload['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($payload['permissions'] ?? []);

        return $this->success([
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions()->pluck('name')->values()->all(),
        ], 201);
    }

    public function update(UpdateAdminGroupRequest $request, int $roleId): JsonResponse
    {
        $this->authorize('manageAdminGroups', Admin::class);

        $role = Role::query()->where('guard_name', 'web')->find($roleId);

        if ($role === null) {
            return $this->error(__('core::messages.api.admin_group_not_found'), 404, 'ADMIN_GROUP_NOT_FOUND');
        }

        $payload = $request->validated();

        if ($role->name === 'admin' && (string) $payload['name'] !== 'admin') {
            return $this->error(__('core::messages.api.admin_group_locked'), 422, 'ADMIN_GROUP_LOCKED');
        }

        $role->fill([
            'name' => (string) $payload['name'],
        ])->save();

        $role->syncPermissions($payload['permissions'] ?? []);

        return $this->success([
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions' => $role->permissions()->pluck('name')->values()->all(),
        ]);
    }

    public function destroy(int $roleId): JsonResponse
    {
        $this->authorize('manageAdminGroups', Admin::class);

        $role = Role::query()->where('guard_name', 'web')->find($roleId);

        if ($role === null) {
            return $this->error(__('core::messages.api.admin_group_not_found'), 404, 'ADMIN_GROUP_NOT_FOUND');
        }

        if ($role->name === 'admin') {
            return $this->error(__('core::messages.api.admin_group_locked'), 422, 'ADMIN_GROUP_LOCKED');
        }

        $role->delete();

        return $this->success([
            'deleted' => true,
        ]);
    }
}
