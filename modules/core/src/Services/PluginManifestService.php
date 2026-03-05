<?php

namespace Pagify\Core\Services;

use Illuminate\Support\Facades\Validator;

class PluginManifestService
{
    /**
     * @return array<string, array{manifest: array<string, mixed>, root_path: string, manifest_path: string}>
     */
    public function discover(): array
    {
        $plugins = [];

        $directories = config('plugins.directories', []);

        if (! is_array($directories)) {
            return [];
        }

        foreach ($directories as $directory) {
            if (! is_string($directory) || ! is_dir($directory)) {
                continue;
            }

            $manifestPaths = glob(rtrim($directory, '/'). '/*/plugin.json');

            if (! is_array($manifestPaths)) {
                continue;
            }

            foreach ($manifestPaths as $manifestPath) {
                if (! is_string($manifestPath)) {
                    continue;
                }

                $manifest = $this->readManifestFile($manifestPath);

                if ($manifest === null) {
                    continue;
                }

                $slug = (string) ($manifest['slug'] ?? '');

                if ($slug === '') {
                    continue;
                }

                $plugins[$slug] = [
                    'manifest' => $manifest,
                    'root_path' => dirname($manifestPath),
                    'manifest_path' => $manifestPath,
                ];
            }
        }

        return $plugins;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function readManifestFile(string $manifestPath): ?array
    {
        if (! is_file($manifestPath)) {
            return null;
        }

        $raw = file_get_contents($manifestPath);

        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return null;
        }

        $validator = Validator::make($decoded, [
            'slug' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'requires' => ['nullable', 'array'],
            'providers' => ['nullable', 'array'],
            'hooks' => ['nullable', 'array'],
            'ui' => ['nullable', 'array'],
            'migrations' => ['nullable', 'array'],
            'permissions' => ['nullable', 'array'],
            'extension_points' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return null;
        }

        return $decoded;
    }
}
