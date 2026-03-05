<?php

namespace Pagify\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Pagify\Media\Jobs\GenerateMediaImageTransformsJob;
use Pagify\Media\Models\MediaAsset;
use Pagify\Media\Models\MediaFolder;

class MediaAssetManager
{
    public function __construct(private readonly MediaStorageManager $storageManager)
    {
    }

    public function upload(
        UploadedFile $file,
        ?MediaFolder $folder = null,
        ?int $siteId = null,
        ?int $uploadedByAdminId = null,
        ?string $altText = null,
        ?string $caption = null,
        ?int $maxImageWidth = null,
    ): MediaAsset {
        $stored = $this->storageManager->storeUploadedFile($file, $folder?->slug);
        $mimeType = (string) $file->getClientMimeType();

        $imageMetadata = $this->resizeStoredImageToWidth(
            disk: $stored['disk'],
            path: $stored['path'],
            mimeType: $mimeType,
            targetWidth: $maxImageWidth,
        );

        $asset = MediaAsset::query()->create([
            'site_id' => $siteId,
            'uuid' => (string) Str::uuid(),
            'folder_id' => $folder?->id,
            'disk' => $stored['disk'],
            'path' => $stored['path'],
            'path_hash' => hash('sha256', $stored['path']),
            'filename' => $stored['filename'],
            'original_name' => (string) $file->getClientOriginalName(),
            'mime_type' => $mimeType,
            'extension' => strtolower((string) $file->getClientOriginalExtension()),
            'size_bytes' => $imageMetadata['size_bytes'] ?? (int) $file->getSize(),
            'width' => $imageMetadata['width'] ?? null,
            'height' => $imageMetadata['height'] ?? null,
            'kind' => $this->resolveKind($mimeType),
            'alt_text' => $altText,
            'caption' => $caption,
            'uploaded_by_admin_id' => $uploadedByAdminId,
            'uploaded_at' => now(),
        ]);

        if (str_starts_with((string) $asset->mime_type, 'image/')) {
            GenerateMediaImageTransformsJob::dispatch($asset->id)
                ->onQueue((string) config('media.image_transforms.queue', 'default'));
        }

        return $asset;
    }

    public function findByPath(?string $path): ?MediaAsset
    {
        if ($path === null || $path === '') {
            return null;
        }

        return MediaAsset::query()
            ->where('path', $path)
            ->first();
    }

    public function resolveUrlByPath(?string $path): ?string
    {
        $asset = $this->findByPath($path);

        if ($asset === null) {
            return null;
        }

        return Storage::disk($asset->disk)->url($asset->path);
    }

    public function delete(MediaAsset $asset): void
    {
        Storage::disk($asset->disk)->delete($asset->path);
        $asset->delete();
    }

    private function resolveKind(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        if (str_contains($mimeType, 'pdf') || str_starts_with($mimeType, 'text/') || str_contains($mimeType, 'officedocument')) {
            return 'document';
        }

        return 'other';
    }

    /**
     * @return array{size_bytes: int, width: int, height: int}|null
     */
    private function resizeStoredImageToWidth(string $disk, string $path, string $mimeType, ?int $targetWidth): ?array
    {
        if ($targetWidth === null || $targetWidth < 1 || ! str_starts_with($mimeType, 'image/')) {
            return null;
        }

        $storage = Storage::disk($disk);

        if (! $storage->exists($path)) {
            return null;
        }

        $rawImage = $storage->get($path);
        if (! is_string($rawImage) || $rawImage === '') {
            return null;
        }

        $source = @imagecreatefromstring($rawImage);
        if ($source === false) {
            return null;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);

        $calculatedHeight = max(1, (int) round($sourceHeight * ($targetWidth / max(1, $sourceWidth))));
        $canvas = imagecreatetruecolor($targetWidth, $calculatedHeight);

        if ($canvas === false) {
            imagedestroy($source);

            return null;
        }

        if (in_array($mimeType, ['image/png', 'image/webp'], true)) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $calculatedHeight, $transparent);
        }

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $calculatedHeight,
            max(1, $sourceWidth),
            max(1, $sourceHeight),
        );

        $resizedImageBinary = $this->encodeImage($canvas, $mimeType);

        imagedestroy($source);
        imagedestroy($canvas);

        if ($resizedImageBinary === '') {
            return null;
        }

        $storage->put($path, $resizedImageBinary);

        return [
            'size_bytes' => strlen($resizedImageBinary),
            'width' => $targetWidth,
            'height' => $calculatedHeight,
        ];
    }

    private function encodeImage(\GdImage $image, string $mimeType): string
    {
        ob_start();

        $encoded = match ($mimeType) {
            'image/jpeg', 'image/jpg' => imagejpeg($image, null, 85),
            'image/png' => imagepng($image, null, 6),
            'image/webp' => function_exists('imagewebp') ? imagewebp($image, null, 82) : false,
            default => false,
        };

        $binary = (string) ob_get_clean();

        if ($encoded === false) {
            return '';
        }

        return $binary;
    }
}
