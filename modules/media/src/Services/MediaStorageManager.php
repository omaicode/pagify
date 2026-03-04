<?php

namespace Modules\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Core\Services\SettingsManager;

class MediaStorageManager
{
    public function __construct(private readonly SettingsManager $settings)
    {
    }

    public function resolveDisk(): string
    {
        $settingsKey = (string) config('media.settings_keys.disk', 'media.storage.disk');
        $configured = (string) $this->settings->get($settingsKey, config('media.storage.default_disk', 'public'));
        $allowed = (array) config('media.storage.allowed_disks', ['public']);

        return in_array($configured, $allowed, true) ? $configured : (string) config('media.storage.default_disk', 'public');
    }

    public function resolveBasePath(): string
    {
        $settingsKey = (string) config('media.settings_keys.base_path', 'media.storage.base_path');
        $configured = (string) $this->settings->get($settingsKey, config('media.storage.base_path', 'media'));

        return trim($configured, '/');
    }

    /**
     * @return array{disk: string, path: string, filename: string}
     */
    public function storeUploadedFile(UploadedFile $file, ?string $folderSlug = null): array
    {
        $disk = $this->resolveDisk();
        $basePath = $this->resolveBasePath();
        $folderPath = $folderSlug !== null && trim($folderSlug) !== '' ? trim($folderSlug, '/') : date('Y/m');
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid()->toString() . ($extension !== '' ? '.' . strtolower($extension) : '');
        $path = trim($basePath . '/' . $folderPath, '/') . '/' . $filename;

        Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path));

        return [
            'disk' => $disk,
            'path' => $path,
            'filename' => $filename,
        ];
    }
}
