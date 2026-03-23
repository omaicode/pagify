<?php

namespace Pagify\Core\Services;

use Illuminate\Support\Facades\Validator;

class FrontendThemeManifestService
{
    /**
     * @return array{manifest: array<string, mixed>|null, errors: array<int, string>}
     */
    public function readManifestFile(string $manifestPath): array
    {
        if (! is_file($manifestPath)) {
            return [
                'manifest' => null,
                'errors' => ['Theme manifest file is missing.'],
            ];
        }

        $raw = file_get_contents($manifestPath);

        if (! is_string($raw) || trim($raw) === '') {
            return [
                'manifest' => null,
                'errors' => ['Theme manifest file is empty.'],
            ];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [
                'manifest' => null,
                'errors' => ['Theme manifest must be a valid JSON object.'],
            ];
        }

        $validator = Validator::make($decoded, [
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'name' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string'],
            'author' => ['nullable', 'string', 'max:255'],
            'requires' => ['nullable', 'array'],
            'supports' => ['nullable', 'array'],
            'render' => ['required', 'array'],
            'render.engine' => ['required', 'string', 'in:wsre'],
            'layouts' => ['nullable', 'array'],
            'layouts.*.file' => ['required_with:layouts', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*\/[a-z0-9]+(?:-[a-z0-9]+)*\.json$/'],
            'layouts.*.label' => ['required_with:layouts', 'string', 'max:255'],
        ], [
        ]);

        if ($validator->fails()) {
            return [
                'manifest' => null,
                'errors' => $validator->errors()->all(),
            ];
        }

        return [
            'manifest' => $decoded,
            'errors' => [],
        ];
    }
}
