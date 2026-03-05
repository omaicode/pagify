<?php

namespace Pagify\Core\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Pagify\Core\Models\PluginState;
use Pagify\Updater\Services\ComposerCommandRunner;
use Throwable;
use ZipArchive;

class PluginManagerService
{
    public function __construct(
        private readonly PluginManifestService $manifests,
        private readonly PluginCompatibilityService $compatibility,
        private readonly ComposerCommandRunner $composer,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $states = $this->canUseStateStore()
            ? PluginState::query()->get()->keyBy('slug')
            : collect();
        $discovered = $this->manifests->discover();
        $slugs = array_values(array_unique(array_merge(array_keys($discovered), $states->keys()->all())));

        $plugins = [];

        foreach ($slugs as $slug) {
            $state = $states->get($slug);
            $discoveredManifest = $discovered[$slug]['manifest'] ?? null;
            $rootPath = $discovered[$slug]['root_path'] ?? ($state?->root_path ?? null);

            $manifest = is_array($state?->manifest) && $state->manifest !== []
                ? $state->manifest
                : (is_array($discoveredManifest) ? $discoveredManifest : []);

            $compatibility = $this->compatibility->evaluate($manifest);

            $plugins[] = [
                'slug' => $slug,
                'name' => (string) ($manifest['name'] ?? $state?->name ?? ucfirst($slug)),
                'description' => (string) ($manifest['description'] ?? $state?->description ?? ''),
                'version' => (string) ($manifest['version'] ?? $state?->version ?? ''),
                'enabled' => (bool) ($state?->enabled ?? false),
                'is_installed' => (bool) ($state?->is_installed ?? isset($discovered[$slug])),
                'source_type' => (string) ($state?->source_type ?? ($rootPath !== null ? 'local' : 'unknown')),
                'package_name' => $state?->package_name,
                'is_compatible' => (bool) $compatibility['compatible'],
                'compatibility_issues' => $compatibility['issues'],
                'safe_mode_disabled_at' => $state?->safe_mode_disabled_at?->toIso8601String(),
                'last_error' => $state?->last_error,
                'root_path' => $rootPath,
                'manifest' => $manifest,
            ];

            if ($this->canUseStateStore()) {
                $this->syncStateFromRuntime(
                    slug: (string) $slug,
                    manifest: $manifest,
                    rootPath: is_string($rootPath) ? $rootPath : null,
                    compatibility: $compatibility,
                    existing: $state,
                );
            }
        }

        usort($plugins, static fn (array $a, array $b): int => $a['name'] <=> $b['name']);

        return $plugins;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $slug): ?array
    {
        foreach ($this->all() as $plugin) {
            if ($plugin['slug'] === $slug) {
                return $plugin;
            }
        }

        return null;
    }

    /**
     * @return array{ok: bool, message: string, plugin?: array<string, mixed>, errors?: array<int, string>}
     */
    public function setEnabled(string $slug, bool $enabled): array
    {
        $plugin = $this->find($slug);

        if ($plugin === null || ! $plugin['is_installed']) {
            return [
                'ok' => false,
                'message' => 'Plugin not found.',
            ];
        }

        if ($enabled && ! $plugin['is_compatible']) {
            return [
                'ok' => false,
                'message' => 'Plugin is not compatible with current runtime.',
                'errors' => is_array($plugin['compatibility_issues']) ? $plugin['compatibility_issues'] : [],
            ];
        }

        if (! $this->canUseStateStore()) {
            return [
                'ok' => false,
                'message' => 'Plugin state storage is not initialized yet.',
            ];
        }

        $state = PluginState::query()->firstOrCreate(['slug' => $slug], [
            'name' => (string) $plugin['name'],
        ]);

        $state->forceFill([
            'enabled' => $enabled,
            'safe_mode_disabled_at' => null,
            'last_error' => null,
        ])->save();

        return [
            'ok' => true,
            'message' => 'Plugin state updated.',
            'plugin' => $this->find($slug),
        ];
    }

    /**
     * @return array{ok: bool, message: string, plugin?: array<string, mixed>, errors?: array<int, string>}
     */
    public function installComposerPackage(string $packageName): array
    {
        if (! preg_match('/^[a-z0-9_.-]+\/[a-z0-9_.-]+$/i', $packageName)) {
            return [
                'ok' => false,
                'message' => 'Invalid package name format. Use vendor/package.',
            ];
        }

        if (! $this->canUseStateStore()) {
            return [
                'ok' => false,
                'message' => 'Plugin state storage is not initialized yet.',
            ];
        }

        $result = $this->composer->run(['require', $packageName]);

        if (! $result['ok']) {
            return [
                'ok' => false,
                'message' => trim((string) ($result['error_output'] ?? 'Composer require failed.')),
            ];
        }

        $manifestPath = base_path(sprintf('vendor/%s/plugin.json', $packageName));
        $manifest = $this->manifests->readManifestFile($manifestPath);

        if ($manifest === null) {
            return [
                'ok' => false,
                'message' => 'Package installed but plugin.json was not found at package root.',
            ];
        }

        $slug = (string) ($manifest['slug'] ?? '');

        if ($slug === '') {
            return [
                'ok' => false,
                'message' => 'Plugin manifest is missing slug.',
            ];
        }

        $compatibility = $this->compatibility->evaluate($manifest);

        $state = PluginState::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => (string) ($manifest['name'] ?? ucfirst($slug)),
                'description' => (string) ($manifest['description'] ?? ''),
                'version' => (string) ($manifest['version'] ?? ''),
                'package_name' => $packageName,
                'source_type' => 'composer',
                'root_path' => dirname($manifestPath),
                'manifest' => $manifest,
                'is_installed' => true,
                'is_compatible' => (bool) $compatibility['compatible'],
                'compatibility_issues' => $compatibility['issues'],
                'enabled' => (bool) $compatibility['compatible'],
                'safe_mode_disabled_at' => null,
                'last_error' => null,
            ]
        );

        return [
            'ok' => true,
            'message' => 'Plugin installed from composer package.',
            'plugin' => $this->toPayload($state),
        ];
    }

    /**
     * @return array{ok: bool, message: string, plugin?: array<string, mixed>}
     */
    public function installZip(UploadedFile $file): array
    {
        if (! $this->canUseStateStore()) {
            return [
                'ok' => false,
                'message' => 'Plugin state storage is not initialized yet.',
            ];
        }

        $tempDir = storage_path('app/plugin-installs/'.uniqid('plugin_', true));
        File::ensureDirectoryExists($tempDir);

        $zipPath = $file->getRealPath();
        $zip = new ZipArchive();

        if (! is_string($zipPath) || $zip->open($zipPath) !== true) {
            File::deleteDirectory($tempDir);

            return [
                'ok' => false,
                'message' => 'Unable to open uploaded zip package.',
            ];
        }

        $zip->extractTo($tempDir);
        $zip->close();

        $manifestPath = $this->findManifestPathInDirectory($tempDir);

        if ($manifestPath === null) {
            File::deleteDirectory($tempDir);

            return [
                'ok' => false,
                'message' => 'plugin.json not found in uploaded package.',
            ];
        }

        $manifest = $this->manifests->readManifestFile($manifestPath);

        if ($manifest === null) {
            File::deleteDirectory($tempDir);

            return [
                'ok' => false,
                'message' => 'Invalid plugin manifest in uploaded package.',
            ];
        }

        $slug = (string) ($manifest['slug'] ?? '');

        if ($slug === '') {
            File::deleteDirectory($tempDir);

            return [
                'ok' => false,
                'message' => 'Plugin slug is required in plugin.json.',
            ];
        }

        $targetRoot = base_path('plugins/'.$slug);
        $sourceRoot = dirname($manifestPath);

        File::ensureDirectoryExists(base_path('plugins'));

        if (is_dir($targetRoot)) {
            File::deleteDirectory($targetRoot);
        }

        File::copyDirectory($sourceRoot, $targetRoot);
        File::deleteDirectory($tempDir);

        $compatibility = $this->compatibility->evaluate($manifest);
        $state = PluginState::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => (string) ($manifest['name'] ?? ucfirst($slug)),
                'description' => (string) ($manifest['description'] ?? ''),
                'version' => (string) ($manifest['version'] ?? ''),
                'source_type' => 'zip',
                'root_path' => $targetRoot,
                'manifest' => $manifest,
                'is_installed' => true,
                'is_compatible' => (bool) $compatibility['compatible'],
                'compatibility_issues' => $compatibility['issues'],
                'enabled' => (bool) $compatibility['compatible'],
                'safe_mode_disabled_at' => null,
                'last_error' => null,
            ]
        );

        return [
            'ok' => true,
            'message' => 'Plugin installed from zip package.',
            'plugin' => $this->toPayload($state),
        ];
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function uninstall(string $slug): array
    {
        if (! $this->canUseStateStore()) {
            return [
                'ok' => false,
                'message' => 'Plugin state storage is not initialized yet.',
            ];
        }

        $state = PluginState::query()->where('slug', $slug)->first();

        if ($state === null) {
            return [
                'ok' => false,
                'message' => 'Plugin not found.',
            ];
        }

        if ($state->source_type === 'composer' && is_string($state->package_name) && $state->package_name !== '') {
            $result = $this->composer->run(['remove', $state->package_name]);

            if (! $result['ok']) {
                return [
                    'ok' => false,
                    'message' => trim((string) ($result['error_output'] ?? 'Composer remove failed.')),
                ];
            }
        }

        if (in_array($state->source_type, ['zip', 'local'], true)
            && is_string($state->root_path)
            && str_starts_with($state->root_path, base_path('plugins'))
            && is_dir($state->root_path)
        ) {
            File::deleteDirectory($state->root_path);
        }

        $state->delete();

        return [
            'ok' => true,
            'message' => 'Plugin uninstalled.',
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function extensionPoints(): array
    {
        $buckets = [
            'field_types' => [],
            'blocks' => [],
            'dashboard_widgets' => [],
            'automation_actions' => [],
            'menu_items' => [],
        ];

        foreach ($this->all() as $plugin) {
            if (! $plugin['enabled'] || ! $plugin['is_compatible']) {
                continue;
            }

            $manifest = $plugin['manifest'] ?? [];
            $extensions = $manifest['extension_points'] ?? [];

            if (! is_array($extensions)) {
                continue;
            }

            foreach (array_keys($buckets) as $key) {
                $items = $extensions[$key] ?? [];

                if (! is_array($items)) {
                    continue;
                }

                foreach ($items as $item) {
                    $buckets[$key][] = [
                        'plugin' => $plugin['slug'],
                        'value' => $item,
                    ];
                }
            }
        }

        return $buckets;
    }

    public function disablePluginFromThrowable(Throwable $throwable): ?string
    {
        if (! (bool) config('plugins.safe_mode.enabled', true)) {
            return null;
        }

        if (! $this->canUseStateStore()) {
            return null;
        }

        $states = PluginState::query()
            ->where('enabled', true)
            ->whereNotNull('root_path')
            ->get();

        if ($states->isEmpty()) {
            return null;
        }

        $files = array_values(array_filter(array_unique(array_merge(
            [$throwable->getFile()],
            array_map(static fn (array $frame): ?string => Arr::get($frame, 'file'), $throwable->getTrace())
        )), static fn ($file): bool => is_string($file) && $file !== ''));

        foreach ($states as $state) {
            $rootPath = (string) $state->root_path;

            foreach ($files as $file) {
                if (str_starts_with($file, $rootPath)) {
                    $state->forceFill([
                        'enabled' => false,
                        'safe_mode_disabled_at' => now(),
                        'last_error' => mb_substr($throwable->getMessage(), 0, 2000),
                    ])->save();

                    return $state->slug;
                }
            }
        }

        return null;
    }

    private function canUseStateStore(): bool
    {
        try {
            return Schema::hasTable('core_plugin_states');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $manifest
     * @param array{compatible: bool, issues: array<int, string>} $compatibility
     */
    private function syncStateFromRuntime(string $slug, array $manifest, ?string $rootPath, array $compatibility, ?PluginState $existing = null): void
    {
        if ($manifest === []) {
            return;
        }

        $state = $existing ?? PluginState::query()->firstOrNew(['slug' => $slug]);

        $state->forceFill([
            'name' => (string) ($manifest['name'] ?? ucfirst($slug)),
            'description' => (string) ($manifest['description'] ?? ''),
            'version' => (string) ($manifest['version'] ?? ''),
            'manifest' => $manifest,
            'root_path' => $rootPath,
            'is_installed' => true,
            'is_compatible' => (bool) $compatibility['compatible'],
            'compatibility_issues' => $compatibility['issues'],
            'source_type' => $state->source_type ?: 'local',
        ]);

        if (! $state->exists) {
            $state->enabled = false;
        }

        $state->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function toPayload(PluginState $state): array
    {
        return [
            'slug' => $state->slug,
            'name' => $state->name,
            'description' => $state->description,
            'version' => $state->version,
            'enabled' => $state->enabled,
            'is_installed' => $state->is_installed,
            'source_type' => $state->source_type,
            'package_name' => $state->package_name,
            'is_compatible' => $state->is_compatible,
            'compatibility_issues' => $state->compatibility_issues ?? [],
            'safe_mode_disabled_at' => $state->safe_mode_disabled_at?->toIso8601String(),
            'last_error' => $state->last_error,
        ];
    }

    private function findManifestPathInDirectory(string $directory): ?string
    {
        $candidates = [
            $directory.'/plugin.json',
        ];

        foreach (glob($directory.'/*/plugin.json') ?: [] as $candidate) {
            if (is_string($candidate)) {
                $candidates[] = $candidate;
            }
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
