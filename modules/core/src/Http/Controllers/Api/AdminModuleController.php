<?php

namespace Modules\Core\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            ])
            ->values();

        return $this->success($data);
    }

    public function update(Request $request, string $module, ModuleRegistry $modules, ModuleStateService $stateService): JsonResponse
    {
        $this->authorize('manageModules', Admin::class);

        if (! $modules->has($module)) {
            return $this->error('Module not found.', 404);
        }

        $payload = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

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
