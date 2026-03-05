<?php

namespace Pagify\Core\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Pagify\Core\Http\Requests\Admin\InstallComposerPluginRequest;
use Pagify\Core\Http\Requests\Admin\InstallZipPluginRequest;
use Pagify\Core\Http\Requests\Admin\UpdateModuleStateRequest;
use Pagify\Core\Http\Resources\PluginStateResource;
use Pagify\Core\Models\Admin;
use Pagify\Core\Services\PluginExtensionRegistry;
use Pagify\Core\Services\PluginManagerService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AdminPluginController extends ApiController
{
    public function index(PluginManagerService $plugins): JsonResponse
    {
        $this->authorize('manageModules', Admin::class);

        $items = collect($plugins->all())
            ->map(static fn (array $plugin): array => PluginStateResource::make($plugin)->resolve())
            ->values()
            ->all();

        return $this->success($items);
    }

    public function update(UpdateModuleStateRequest $request, string $plugin, PluginManagerService $plugins): JsonResponse
    {
        $this->authorize('manageModules', Admin::class);

        $result = $plugins->setEnabled($plugin, (bool) $request->boolean('enabled'));

        if (! $result['ok']) {
            return $this->error(
                (string) $result['message'],
                422,
                'PLUGIN_STATE_UPDATE_FAILED',
                ['issues' => $result['errors'] ?? []]
            );
        }

        $payload = is_array($result['plugin'] ?? null) ? PluginStateResource::make($result['plugin'])->resolve() : null;

        return $this->success($payload);
    }

    public function installComposer(InstallComposerPluginRequest $request, PluginManagerService $plugins): JsonResponse
    {
        $this->authorize('manageModules', Admin::class);

        $result = $plugins->installComposerPackage((string) $request->input('package_name'));

        if (! $result['ok']) {
            return $this->error((string) $result['message'], 422, 'PLUGIN_INSTALL_FAILED');
        }

        $payload = is_array($result['plugin'] ?? null) ? PluginStateResource::make($result['plugin'])->resolve() : null;

        return $this->success([
            'message' => $result['message'],
            'plugin' => $payload,
        ], 201);
    }

    public function installZip(InstallZipPluginRequest $request, PluginManagerService $plugins): JsonResponse
    {
        $this->authorize('manageModules', Admin::class);

        $file = $request->file('plugin_zip');

        if (! $file instanceof UploadedFile) {
            return $this->error('Plugin zip upload is required.', 422, 'PLUGIN_INSTALL_FAILED');
        }

        $result = $plugins->installZip($file);

        if (! $result['ok']) {
            return $this->error((string) $result['message'], 422, 'PLUGIN_INSTALL_FAILED');
        }

        $payload = is_array($result['plugin'] ?? null) ? PluginStateResource::make($result['plugin'])->resolve() : null;

        return $this->success([
            'message' => $result['message'],
            'plugin' => $payload,
        ], 201);
    }

    public function destroy(string $plugin, PluginManagerService $plugins): JsonResponse
    {
        $this->authorize('manageModules', Admin::class);

        $result = $plugins->uninstall($plugin);

        if (! $result['ok']) {
            return $this->error((string) $result['message'], 422, 'PLUGIN_UNINSTALL_FAILED');
        }

        return $this->success([
            'plugin' => $plugin,
            'message' => $result['message'],
        ]);
    }

    public function extensions(PluginExtensionRegistry $extensions): JsonResponse
    {
        $this->authorize('manageModules', Admin::class);

        return $this->success($extensions->all());
    }
}
