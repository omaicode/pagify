<?php

namespace Modules\Media\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Media\Models\MediaAsset;
use Modules\Media\Models\MediaAssetTransform;
use Throwable;

class GenerateMediaImageTransformsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function __construct(public readonly int $assetId)
    {
    }

    public function handle(): void
    {
        $asset = MediaAsset::query()->withoutGlobalScopes()->find($this->assetId);

        if ($asset === null) {
            return;
        }

        if (! str_starts_with((string) $asset->mime_type, 'image/')) {
            return;
        }

        try {
            $disk = Storage::disk($asset->disk);

            if (! $disk->exists($asset->path)) {
                $this->markFailed($asset, 'thumbnail', 'Original file does not exist on disk.');
                $this->markFailed($asset, 'webp', 'Original file does not exist on disk.');

                return;
            }

            $rawImage = $disk->get($asset->path);
            if (! is_string($rawImage) || $rawImage === '') {
                $this->markFailed($asset, 'thumbnail', 'Original image payload is empty.');
                $this->markFailed($asset, 'webp', 'Original image payload is empty.');

                return;
            }

            $source = @imagecreatefromstring($rawImage);
            if ($source === false) {
                $this->markFailed($asset, 'thumbnail', 'Unable to decode image data.');
                $this->markFailed($asset, 'webp', 'Unable to decode image data.');

                return;
            }

            $sourceWidth = imagesx($source);
            $sourceHeight = imagesy($source);

            if ($asset->width === null || $asset->height === null) {
                $asset->forceFill([
                    'width' => $sourceWidth,
                    'height' => $sourceHeight,
                ])->save();
            }

            $this->generateThumbnail($asset, $source, $sourceWidth, $sourceHeight);
            $this->generateWebp($asset, $source, $sourceWidth, $sourceHeight);

            imagedestroy($source);
        } catch (Throwable $exception) {
            Log::error('Media image transform job failed.', [
                'asset_id' => $asset->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            $this->markFailed($asset, 'thumbnail', $exception->getMessage());
            $this->markFailed($asset, 'webp', $exception->getMessage());

            throw $exception;
        }
    }

    private function generateThumbnail(MediaAsset $asset, \GdImage $source, int $sourceWidth, int $sourceHeight): void
    {
        $this->markProcessing($asset, 'thumbnail');

        $targetWidth = (int) config('media.image_transforms.thumbnail_width', 480);
        $targetHeight = (int) config('media.image_transforms.thumbnail_height', 320);

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        $sourceRatio = $sourceWidth / max($sourceHeight, 1);
        $targetRatio = $targetWidth / max($targetHeight, 1);

        if ($sourceRatio > $targetRatio) {
            $cropHeight = $sourceHeight;
            $cropWidth = (int) round($sourceHeight * $targetRatio);
            $cropX = (int) floor(($sourceWidth - $cropWidth) / 2);
            $cropY = 0;
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = (int) round($sourceWidth / max($targetRatio, 0.0001));
            $cropX = 0;
            $cropY = (int) floor(($sourceHeight - $cropHeight) / 2);
        }

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            $cropX,
            $cropY,
            $targetWidth,
            $targetHeight,
            max($cropWidth, 1),
            max($cropHeight, 1),
        );

        ob_start();
        imagejpeg($canvas, null, 85);
        $binary = (string) ob_get_clean();
        imagedestroy($canvas);

        $path = $this->buildTransformPath($asset->path, 'thumbnail', 'jpg');
        Storage::disk($asset->disk)->put($path, $binary);

        $this->markCompleted($asset, 'thumbnail', [
            'disk' => $asset->disk,
            'path' => $path,
            'mime_type' => 'image/jpeg',
            'width' => $targetWidth,
            'height' => $targetHeight,
            'size_bytes' => strlen($binary),
        ]);
    }

    private function generateWebp(MediaAsset $asset, \GdImage $source, int $sourceWidth, int $sourceHeight): void
    {
        $this->markProcessing($asset, 'webp');

        if (! function_exists('imagewebp')) {
            $this->markFailed($asset, 'webp', 'WebP support is not available in GD.');

            return;
        }

        ob_start();
        imagewebp($source, null, 82);
        $binary = (string) ob_get_clean();

        if ($binary === '') {
            $this->markFailed($asset, 'webp', 'Failed to encode WebP image.');

            return;
        }

        $path = $this->buildTransformPath($asset->path, 'webp', 'webp');
        Storage::disk($asset->disk)->put($path, $binary);

        $this->markCompleted($asset, 'webp', [
            'disk' => $asset->disk,
            'path' => $path,
            'mime_type' => 'image/webp',
            'width' => $sourceWidth,
            'height' => $sourceHeight,
            'size_bytes' => strlen($binary),
        ]);
    }

    private function buildTransformPath(string $originalPath, string $profile, string $extension): string
    {
        $directory = trim(dirname($originalPath), '/.');
        $baseName = pathinfo($originalPath, PATHINFO_FILENAME);

        return trim($directory . '/_derived/' . $baseName . '__' . $profile . '.' . $extension, '/');
    }

    private function markProcessing(MediaAsset $asset, string $profile): void
    {
        MediaAssetTransform::query()->updateOrCreate(
            [
                'asset_id' => $asset->id,
                'profile' => $profile,
                'variant' => 'default',
            ],
            [
                'site_id' => $asset->site_id,
                'status' => 'processing',
                'error_message' => null,
            ],
        );
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function markCompleted(MediaAsset $asset, string $profile, array $attributes): void
    {
        MediaAssetTransform::query()->updateOrCreate(
            [
                'asset_id' => $asset->id,
                'profile' => $profile,
                'variant' => 'default',
            ],
            [
                'site_id' => $asset->site_id,
                'status' => 'completed',
                'disk' => $attributes['disk'] ?? null,
                'path' => $attributes['path'] ?? null,
                'mime_type' => $attributes['mime_type'] ?? null,
                'width' => $attributes['width'] ?? null,
                'height' => $attributes['height'] ?? null,
                'size_bytes' => $attributes['size_bytes'] ?? null,
                'error_message' => null,
                'generated_at' => now(),
            ],
        );
    }

    private function markFailed(MediaAsset $asset, string $profile, string $error): void
    {
        MediaAssetTransform::query()->updateOrCreate(
            [
                'asset_id' => $asset->id,
                'profile' => $profile,
                'variant' => 'default',
            ],
            [
                'site_id' => $asset->site_id,
                'status' => 'failed',
                'error_message' => $error,
            ],
        );
    }
}
