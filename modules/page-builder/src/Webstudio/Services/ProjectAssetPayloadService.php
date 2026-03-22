<?php

namespace Pagify\PageBuilder\Webstudio\Services;

use Illuminate\Support\Facades\Storage;
use Pagify\Media\Models\MediaAsset;

class ProjectAssetPayloadService
{
	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function listForProject(string $projectId): array
	{
		$assets = MediaAsset::query()
			->orderByDesc('created_at')
			->limit(100)
			->get();

		return $assets->map(fn (MediaAsset $asset): array => $this->mapOne($asset, $projectId))->values()->all();
	}

	/**
	 * @return array<string, mixed>
	 */
	public function mapOne(MediaAsset $asset, string $projectId): array
	{
		$type = str_starts_with((string) $asset->mime_type, 'image/') ? 'image' : 'file';
		$storageUrl = Storage::disk((string) $asset->disk)->url((string) $asset->path);
		$storagePath = parse_url($storageUrl, PHP_URL_PATH);
		$assetName = is_string($storagePath) && $storagePath !== ''
			? ltrim($storagePath, '/')
			: ltrim((string) $asset->path, '/');

		$mapped = [
			'id' => (string) $asset->id,
			'name' => $assetName,
			'filename' => (string) ($asset->original_name ?: $asset->filename),
			'format' => (string) ($asset->extension ?: pathinfo((string) $asset->filename, PATHINFO_EXTENSION)),
			'type' => $type,
			'description' => (string) ($asset->alt_text ?? ''),
			'createdAt' => $asset->created_at?->toIso8601String() ?? now()->toIso8601String(),
			'projectId' => $projectId,
			'size' => (int) ($asset->size_bytes ?? 0),
			'meta' => [],
		];

		if ($type === 'image') {
			$mapped['meta'] = [
				'width' => (int) ($asset->width ?? 0),
				'height' => (int) ($asset->height ?? 0),
			];
		}

		return $mapped;
	}
}
