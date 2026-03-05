<?php

namespace Pagify\Core\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Pagify\Core\Http\Requests\Admin\StoreManagedAdminRequest;
use Pagify\Core\Http\Requests\Admin\UpdateManagedAdminRequest;
use Pagify\Core\Models\Admin;
use Spatie\Permission\Models\Role;

class AdminManagerController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('manageAdmins', Admin::class);

        /** @var Admin $authAdmin */
        $authAdmin = $request->user('web');

        $admins = Admin::query()
            ->where('site_id', $authAdmin->site_id)
            ->with('roles:id,name')
            ->orderBy('name')
            ->get(['id', 'site_id', 'name', 'username', 'email', 'locale', 'created_at']);

        return $this->success($admins->map(static fn (Admin $admin): array => [
            'id' => $admin->id,
            'site_id' => $admin->site_id,
            'name' => $admin->name,
            'username' => $admin->username,
            'email' => $admin->email,
            'locale' => $admin->locale,
            'roles' => $admin->roles->pluck('name')->values()->all(),
            'created_at' => optional($admin->created_at)?->toDateTimeString(),
        ])->values());
    }

    public function store(StoreManagedAdminRequest $request): JsonResponse
    {
        $this->authorize('manageAdmins', Admin::class);

        /** @var Admin $authAdmin */
        $authAdmin = $request->user('web');

        $payload = $request->validated();

        $admin = Admin::query()->create([
            'site_id' => $authAdmin->site_id,
            'name' => (string) $payload['name'],
            'username' => (string) $payload['username'],
            'email' => $payload['email'] ?? null,
            'locale' => (string) $payload['locale'],
            'password' => Hash::make((string) $payload['password']),
        ]);

        $roles = $this->filterWebRoleNames($payload['roles'] ?? []);
        $admin->syncRoles($roles);

        return $this->success([
            'id' => $admin->id,
            'site_id' => $admin->site_id,
            'name' => $admin->name,
            'username' => $admin->username,
            'email' => $admin->email,
            'locale' => $admin->locale,
            'roles' => $admin->roles()->pluck('name')->values()->all(),
        ], 201);
    }

    public function update(UpdateManagedAdminRequest $request, int $managedAdminId): JsonResponse
    {
        $this->authorize('manageAdmins', Admin::class);

        /** @var Admin $authAdmin */
        $authAdmin = $request->user('web');

        $admin = Admin::query()
            ->where('site_id', $authAdmin->site_id)
            ->find($managedAdminId);

        if ($admin === null) {
            return $this->error(__('core::messages.api.admin_not_found'), 404, 'ADMIN_NOT_FOUND');
        }

        $payload = $request->validated();

        $admin->fill([
            'name' => (string) $payload['name'],
            'username' => (string) $payload['username'],
            'email' => $payload['email'] ?? null,
            'locale' => (string) $payload['locale'],
        ]);

        if (isset($payload['password']) && is_string($payload['password']) && $payload['password'] !== '') {
            $admin->password = Hash::make($payload['password']);
        }

        $admin->save();

        $roles = $this->filterWebRoleNames($payload['roles'] ?? []);
        $admin->syncRoles($roles);

        return $this->success([
            'id' => $admin->id,
            'site_id' => $admin->site_id,
            'name' => $admin->name,
            'username' => $admin->username,
            'email' => $admin->email,
            'locale' => $admin->locale,
            'roles' => $admin->roles()->pluck('name')->values()->all(),
        ]);
    }

    public function destroy(Request $request, int $managedAdminId): JsonResponse
    {
        $this->authorize('manageAdmins', Admin::class);

        /** @var Admin $authAdmin */
        $authAdmin = $request->user('web');

        if ($authAdmin->id === $managedAdminId) {
            return $this->error(__('core::messages.api.admin_delete_self_forbidden'), 422, 'ADMIN_DELETE_SELF_FORBIDDEN');
        }

        $admin = Admin::query()
            ->where('site_id', $authAdmin->site_id)
            ->find($managedAdminId);

        if ($admin === null) {
            return $this->error(__('core::messages.api.admin_not_found'), 404, 'ADMIN_NOT_FOUND');
        }

        $admin->delete();

        return $this->success([
            'deleted' => true,
        ]);
    }

    /**
     * @param array<int, mixed> $roles
     * @return array<int, string>
     */
    private function filterWebRoleNames(array $roles): array
    {
        if ($roles === []) {
            return [];
        }

        return Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $roles)
            ->pluck('name')
            ->values()
            ->all();
    }
}
