<?php

namespace Pagify\Updater\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pagify\Core\Http\Controllers\Api\ApiController;
use Pagify\Core\Models\Admin;
use Pagify\Core\Services\ModuleRegistry;
use Pagify\Updater\Jobs\RunUpdaterExecutionJob;
use Pagify\Updater\Models\UpdaterExecution;
use Pagify\Updater\Services\UpdaterExecutionService;

class AdminUpdaterExecutionController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->authorize('manageUpdater', Admin::class);

        $executions = UpdaterExecution::query()
            ->with('items')
            ->latest('id')
            ->limit(50)
            ->get();

        return $this->success($executions);
    }

    public function show(UpdaterExecution $execution): JsonResponse
    {
        $this->authorize('manageUpdater', Admin::class);

        $execution->load('items');

        $result = is_array($execution->result) ? $execution->result : [];
        $output = is_string($result['output'] ?? null) ? $result['output'] : '';
        $errorOutput = is_string($result['error_output'] ?? null) ? $result['error_output'] : '';

        $outputExcerpt = $this->excerpt($output);
        $errorExcerpt = $this->excerpt($errorOutput);

        return $this->success([
            'id' => $execution->id,
            'status' => $execution->status,
            'target_type' => $execution->target_type,
            'target_value' => $execution->target_value,
            'requested_by_admin_id' => $execution->requested_by_admin_id,
            'started_at' => $execution->started_at?->toIso8601String(),
            'finished_at' => $execution->finished_at?->toIso8601String(),
            'created_at' => $execution->created_at?->toIso8601String(),
            'updated_at' => $execution->updated_at?->toIso8601String(),
            'items' => $execution->items->map(static function ($item): array {
                return [
                    'id' => $item->id,
                    'module_slug' => $item->module_slug,
                    'package_name' => $item->package_name,
                    'status' => $item->status,
                    'from_version' => $item->from_version,
                    'to_version' => $item->to_version,
                    'error_message' => $item->error_message,
                ];
            })->values()->all(),
            'log' => [
                'exit_code' => is_int($result['exit_code'] ?? null) ? $result['exit_code'] : null,
                'output_excerpt' => $outputExcerpt['text'],
                'output_truncated' => $outputExcerpt['truncated'],
                'error_output_excerpt' => $errorExcerpt['text'],
                'error_output_truncated' => $errorExcerpt['truncated'],
            ],
        ]);
    }

    public function dryRun(Request $request, ModuleRegistry $modules, UpdaterExecutionService $service): JsonResponse
    {
        $this->authorize('manageUpdater', Admin::class);

        $targetType = (string) $request->input('target_type', 'all');
        $targetValue = $request->input('target_value');
        $targetValue = is_string($targetValue) && $targetValue !== '' ? $targetValue : null;

        if (! in_array($targetType, ['all', 'module'], true)) {
            return $this->error('Invalid target type.', 422, 'INVALID_TARGET_TYPE');
        }

        if ($targetType === 'module') {
            if ($targetValue === null) {
                return $this->error('Module slug is required.', 422, 'MODULE_REQUIRED');
            }

            if (! $modules->has($targetValue)) {
                return $this->error('Module not found.', 404, 'MODULE_NOT_FOUND');
            }
        }

        return $this->success($service->dryRunPlan($targetType, $targetValue));
    }

    public function updateModule(string $module, ModuleRegistry $modules, UpdaterExecutionService $service, Request $request): JsonResponse
    {
        $this->authorize('manageUpdater', Admin::class);

        if (! $modules->has($module)) {
            return $this->error('Module not found.', 404, 'MODULE_NOT_FOUND');
        }

        /** @var Admin $admin */
        $admin = $request->user('web');
        $execution = $service->createExecution($admin, 'module', $module);

        RunUpdaterExecutionJob::dispatch($execution->id);

        return $this->success([
            'execution_id' => $execution->id,
            'status' => $execution->status,
            'target_type' => $execution->target_type,
            'target_value' => $execution->target_value,
        ], 202);
    }

    public function updateAll(UpdaterExecutionService $service, Request $request): JsonResponse
    {
        $this->authorize('manageUpdater', Admin::class);

        /** @var Admin $admin */
        $admin = $request->user('web');
        $execution = $service->createExecution($admin, 'all');

        RunUpdaterExecutionJob::dispatch($execution->id);

        return $this->success([
            'execution_id' => $execution->id,
            'status' => $execution->status,
            'target_type' => $execution->target_type,
            'target_value' => $execution->target_value,
        ], 202);
    }

    public function rollback(UpdaterExecution $execution, UpdaterExecutionService $service): JsonResponse
    {
        $this->authorize('manageUpdater', Admin::class);

        $result = $service->rollback($execution);

        if (! $result['ok']) {
            return $this->error((string) $result['message'], 422, 'ROLLBACK_FAILED');
        }

        return $this->success($result);
    }

    /**
     * @return array{text: string, truncated: bool}
     */
    private function excerpt(string $value): array
    {
        $trimmed = trim($value);
        $limit = 2000;

        if (mb_strlen($trimmed) <= $limit) {
            return [
                'text' => $trimmed,
                'truncated' => false,
            ];
        }

        return [
            'text' => mb_substr($trimmed, 0, $limit),
            'truncated' => true,
        ];
    }
}
