<?php

namespace Pagify\Updater\Services;

use Illuminate\Support\Facades\Storage;
use Pagify\Core\Models\Admin;
use Pagify\Updater\Models\UpdaterExecution;

class UpdaterExecutionService
{
    public function __construct(
        private readonly ModulePackageResolver $resolver,
        private readonly ComposerCommandRunner $composer,
    ) {
    }

    public function createExecution(?Admin $admin, string $targetType, ?string $targetValue = null): UpdaterExecution
    {
        return UpdaterExecution::query()->create([
            'requested_by_admin_id' => $admin?->getKey(),
            'target_type' => $targetType,
            'target_value' => $targetValue,
            'status' => 'queued',
            'meta' => [
                'requested_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * @return array{target_type: string, target_value: string|null, items: array<int, array{module_slug: string, package_name: string, installed_version: string|null}>, summary: array{total_modules: int, resolved_packages: int}}
     */
    public function dryRunPlan(string $targetType, ?string $targetValue = null): array
    {
        $packagesByModule = $this->resolver->resolveTargetPackages($targetType, $targetValue);
        $versions = $this->readComposerLockVersions();

        $items = [];

        foreach ($packagesByModule as $moduleSlug => $packageName) {
            $items[] = [
                'module_slug' => $moduleSlug,
                'package_name' => $packageName,
                'installed_version' => $versions[$packageName] ?? null,
            ];
        }

        return [
            'target_type' => $targetType,
            'target_value' => $targetValue,
            'items' => $items,
            'summary' => [
                'total_modules' => count($items),
                'resolved_packages' => count($items),
            ],
        ];
    }

    public function markRunning(UpdaterExecution $execution): void
    {
        $execution->forceFill([
            'status' => 'running',
            'started_at' => now(),
        ])->save();
    }

    public function execute(UpdaterExecution $execution): UpdaterExecution
    {
        $execution->refresh();
        $this->markRunning($execution);

        $packagesByModule = $this->resolver->resolveTargetPackages($execution->target_type, $execution->target_value);

        if ($packagesByModule === []) {
            $execution->forceFill([
                'status' => 'failed',
                'finished_at' => now(),
                'result' => ['message' => 'No packages resolved for target.'],
            ])->save();

            return $execution->fresh();
        }

        $versionsBefore = $this->readComposerLockVersions();
        $snapshotPath = $this->createLockSnapshot($execution->id);

        $items = [];

        foreach ($packagesByModule as $moduleSlug => $packageName) {
            $items[$moduleSlug] = $execution->items()->create([
                'module_slug' => $moduleSlug,
                'package_name' => $packageName,
                'status' => 'running',
                'from_version' => $versionsBefore[$packageName] ?? null,
            ]);
        }

        $result = $this->composer->run(array_merge(['update'], array_values($packagesByModule)));
        $versionsAfter = $this->readComposerLockVersions();

        foreach ($packagesByModule as $moduleSlug => $packageName) {
            $item = $items[$moduleSlug];
            $errorMessage = null;

            if (! $result['ok']) {
                $errorMessage = trim((string) $result['error_output']);

                if ($errorMessage === '') {
                    $errorMessage = trim((string) $result['output']);
                }
            }

            $item->forceFill([
                'status' => $result['ok'] ? 'succeeded' : 'failed',
                'to_version' => $versionsAfter[$packageName] ?? $item->from_version,
                'error_message' => $errorMessage,
            ])->save();
        }

        $execution->forceFill([
            'status' => $result['ok'] ? 'succeeded' : 'failed',
            'snapshot_path' => $snapshotPath,
            'finished_at' => now(),
            'result' => [
                'command' => $result['command'],
                'exit_code' => $result['exit_code'],
                'output' => $result['output'],
                'error_output' => $result['error_output'],
            ],
        ])->save();

        return $execution->fresh('items');
    }

    /**
     * @return array{ok: bool, message: string, command?: array<int, string>, exit_code?: int, output?: string, error_output?: string}
     */
    public function rollback(UpdaterExecution $execution): array
    {
        if (! is_string($execution->snapshot_path) || $execution->snapshot_path === '') {
            return [
                'ok' => false,
                'message' => 'Execution does not have rollback snapshot.',
            ];
        }

        $disk = (string) config('updater.rollback.snapshot_disk', 'local');

        if (! Storage::disk($disk)->exists($execution->snapshot_path)) {
            return [
                'ok' => false,
                'message' => 'Rollback snapshot is missing.',
            ];
        }

        $contents = Storage::disk($disk)->get($execution->snapshot_path);

        if ($contents === '') {
            return [
                'ok' => false,
                'message' => 'Rollback snapshot is empty.',
            ];
        }

        file_put_contents(base_path('composer.lock'), $contents);

        $result = $this->composer->run(['install']);

        return [
            'ok' => $result['ok'],
            'message' => $result['ok'] ? 'Rollback completed.' : 'Rollback failed.',
            'command' => $result['command'],
            'exit_code' => $result['exit_code'],
            'output' => $result['output'],
            'error_output' => $result['error_output'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function readComposerLockVersions(): array
    {
        $lockPath = base_path('composer.lock');

        if (! is_file($lockPath)) {
            return [];
        }

        $raw = file_get_contents($lockPath);

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        $packages = [];

        foreach (['packages', 'packages-dev'] as $group) {
            $entries = $decoded[$group] ?? [];

            if (! is_array($entries)) {
                continue;
            }

            foreach ($entries as $package) {
                if (! is_array($package)) {
                    continue;
                }

                $name = $package['name'] ?? null;
                $version = $package['version'] ?? null;

                if (! is_string($name) || ! is_string($version)) {
                    continue;
                }

                $packages[$name] = $version;
            }
        }

        return $packages;
    }

    private function createLockSnapshot(int $executionId): ?string
    {
        $lockPath = base_path('composer.lock');

        if (! is_file($lockPath)) {
            return null;
        }

        $raw = file_get_contents($lockPath);

        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $disk = (string) config('updater.rollback.snapshot_disk', 'local');
        $directory = trim((string) config('updater.rollback.snapshot_directory', 'updater/snapshots'), '/');
        $path = sprintf('%s/execution-%d.lock', $directory, $executionId);

        Storage::disk($disk)->put($path, $raw);

        return $path;
    }
}
