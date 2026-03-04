<?php

namespace Modules\Core\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Requests\Admin\UpdateModuleStateRequest;
use Modules\Core\Http\Resources\ModuleStateResource;
use Modules\Core\Models\Admin;
use Modules\Core\Services\ModuleRegistry;
use Modules\Core\Services\ModuleStateService;

class AdminModuleController extends ApiController
{
    public function index(ModuleRegistry $modules): JsonResponse
    {
        $this->authorize('manageModules', Admin::class);

        $data = collect($modules->all())
            ->map(static fn (array $module, string $slug): array => [
                'slug' => $slug,
                'name' => $module['name'] ?? ucfirst($slug),
                'description' => $module['description'] ?? null,
                'enabled' => (bool) ($module['enabled'] ?? false),
                'can_disable' => $slug !== 'core',
            ])
            ->values();

        return $this->success(ModuleStateResource::collection($data)->resolve());
    }

    public function update(UpdateModuleStateRequest $request, string $module, ModuleRegistry $modules, ModuleStateService $stateService): JsonResponse
    {
        $this->authorize('manageModules', Admin::class);

        if (! $modules->has($module)) {
            return $this->error(__('core::messages.api.module_not_found'), 404, 'MODULE_NOT_FOUND');
        }

        $payload = $request->validated();

        if ($module === 'core' && (bool) $payload['enabled'] === false) {
            return $this->error(__('core::messages.api.module_locked'), 422, 'MODULE_LOCKED');
        }

        $stateService->setEnabled($module, (bool) $payload['enabled']);

        return $this->success([
            'module' => $module,
            'enabled' => (bool) $payload['enabled'],
        ]);
    }

    public function health(ModuleRegistry $modules): JsonResponse
    {
        $this->authorize('manageModules', Admin::class);

        return $this->success($modules->health());
    }
}
