<?php

namespace Pagify\Core\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Pagify\Core\Http\Requests\Admin\StorePermissionRequest;
use Pagify\Core\Http\Requests\Admin\UpdatePermissionRequest;
use Pagify\Core\Models\Admin;
use Spatie\Permission\Models\Permission;

class AdminPermissionController extends ApiController
{
    /** @var array<int, string> */
    private const PROTECTED_PERMISSIONS = [
        'core.permission.manage',
        'core.admin.manage',
        'core.admin.group.manage',
    ];

    public function index(): JsonResponse
    {
        $this->authorize('managePermissions', Admin::class);

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name', 'created_at']);

        return $this->success($permissions->map(static fn (Permission $permission): array => [
            'id' => $permission->id,
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
            'created_at' => optional($permission->created_at)?->toDateTimeString(),
        ])->values());
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $this->authorize('managePermissions', Admin::class);

        $permission = Permission::query()->create([
            'name' => (string) $request->validated('name'),
            'guard_name' => 'web',
        ]);

        return $this->success([
            'id' => $permission->id,
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
        ], 201);
    }

    public function update(UpdatePermissionRequest $request, int $permissionId): JsonResponse
    {
        $this->authorize('managePermissions', Admin::class);

        $permission = Permission::query()->where('guard_name', 'web')->find($permissionId);

        if ($permission === null) {
            return $this->error(__('core::messages.api.permission_not_found'), 404, 'PERMISSION_NOT_FOUND');
        }

        $permission->fill([
            'name' => (string) $request->validated('name'),
        ])->save();

        return $this->success([
            'id' => $permission->id,
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
        ]);
    }

    public function destroy(int $permissionId): JsonResponse
    {
        $this->authorize('managePermissions', Admin::class);

        $permission = Permission::query()->where('guard_name', 'web')->find($permissionId);

        if ($permission === null) {
            return $this->error(__('core::messages.api.permission_not_found'), 404, 'PERMISSION_NOT_FOUND');
        }

        if (in_array($permission->name, self::PROTECTED_PERMISSIONS, true)) {
            return $this->error(__('core::messages.api.permission_locked'), 422, 'PERMISSION_LOCKED');
        }

        $permission->delete();

        return $this->success([
            'deleted' => true,
        ]);
    }
}
