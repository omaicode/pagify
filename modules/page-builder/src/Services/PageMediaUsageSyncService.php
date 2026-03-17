<?php

namespace Pagify\PageBuilder\Services;

use Pagify\Media\Models\MediaAsset;
use Pagify\Media\Models\MediaUsage;
use Pagify\PageBuilder\Models\Page;

class PageMediaUsageSyncService
{
	private const CONTEXT_TYPE = 'page_builder.page';

	public function syncForPage(Page $page): void
	{
		$this->clearForPage($page);

		$references = $this->extractAssetReferences((array) ($page->layout_json ?? []));

		if ($references === []) {
			return;
		}

		$referenceCollection = collect($references);
		$uuids = $referenceCollection
			->where('kind', 'uuid')
			->pluck('value')
			->filter(static fn (mixed $value): bool => is_string($value) && $value !== '')
			->unique()
			->values()
			->all();

		$paths = $referenceCollection
			->where('kind', 'path')
			->pluck('value')
			->filter(static fn (mixed $value): bool => is_string($value) && $value !== '')
			->unique()
			->values()
			->all();

		$assets = MediaAsset::query()
			->where(static function ($query) use ($uuids, $paths): void {
				if ($uuids !== []) {
					$query->whereIn('uuid', $uuids);
				}

				if ($paths !== []) {
					if ($uuids !== []) {
						$query->orWhereIn('path', $paths);
					} else {
						$query->whereIn('path', $paths);
					}
				}
			})
			->get();

		if ($assets->isEmpty()) {
			return;
		}

		$assetByUuid = $assets
			->filter(static fn (MediaAsset $asset): bool => is_string($asset->uuid) && $asset->uuid !== '')
			->keyBy('uuid');
		$assetByPath = $assets
			->filter(static fn (MediaAsset $asset): bool => is_string($asset->path) && $asset->path !== '')
			->keyBy('path');

		$rows = [];

		foreach ($references as $reference) {
			$kind = (string) ($reference['kind'] ?? '');
			$value = (string) ($reference['value'] ?? '');
			$referencePath = (string) ($reference['reference_path'] ?? 'layout.webstudio');

			$asset = $kind === 'uuid'
				? $assetByUuid->get($value)
				: $assetByPath->get($value);

			if (! $asset instanceof MediaAsset) {
				continue;
			}

			$rows[] = [
				'site_id' => $page->site_id,
				'asset_id' => $asset->id,
				'context_type' => self::CONTEXT_TYPE,
				'context_id' => (string) $page->id,
				'field_key' => 'layout.webstudio',
				'reference_path' => $referencePath,
				'meta' => json_encode([
					'page_slug' => $page->slug,
					'match_kind' => $kind,
					'match_value' => $value,
				], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
				'created_at' => now(),
				'updated_at' => now(),
			];
		}

		if ($rows === []) {
			return;
		}

		$deduplicatedRows = collect($rows)
			->unique(static fn (array $row): string => $row['asset_id'] . '|' . ($row['reference_path'] ?? ''))
			->values()
			->all();

		MediaUsage::query()->insert($deduplicatedRows);
	}

	public function clearForPage(Page $page): void
	{
		MediaUsage::query()
			->where('context_type', self::CONTEXT_TYPE)
			->where('context_id', (string) $page->id)
			->delete();
	}

	/**
	 * @param array<string, mixed> $layout
	 * @return array<int, array{kind: string, value: string, reference_path: string}>
	 */
	private function extractAssetReferences(array $layout): array
	{
		$webstudio = (array) ($layout['webstudio'] ?? []);
		$references = [];

		$this->walkValue($webstudio, 'layout.webstudio', $references);

		return collect($references)
			->filter(static fn (array $item): bool => ($item['value'] ?? '') !== '')
			->values()
			->all();
	}

	/**
	 * @param mixed $value
	 * @param array<int, array{kind: string, value: string, reference_path: string}> $references
	 */
	private function walkValue(mixed $value, string $path, array &$references): void
	{
		if (is_array($value)) {
			foreach ($value as $key => $item) {
				$keyPath = is_string($key) ? $key : (string) $key;
				$this->walkValue($item, $path . '.' . $keyPath, $references);
			}

			return;
		}

		if (! is_string($value) || trim($value) === '') {
			return;
		}

		$normalized = trim($value);

		if (preg_match_all('/\b[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\b/i', $normalized, $matches) === 1) {
			foreach ((array) ($matches[0] ?? []) as $uuid) {
				$references[] = [
					'kind' => 'uuid',
					'value' => strtolower((string) $uuid),
					'reference_path' => $path,
				];
			}
		}

		if ($this->looksLikeAssetPath($normalized)) {
			$references[] = [
				'kind' => 'path',
				'value' => $this->normalizeAssetPath($normalized),
				'reference_path' => $path,
			];
		}
	}

	private function looksLikeAssetPath(string $value): bool
	{
		if (str_starts_with($value, 'media/')) {
			return true;
		}

		if (str_starts_with($value, '/storage/media/')) {
			return true;
		}

		if (str_contains($value, '/storage/media/')) {
			return true;
		}

		return false;
	}

	private function normalizeAssetPath(string $value): string
	{
		$trimmed = trim($value);
		$parsedPath = (string) parse_url($trimmed, PHP_URL_PATH);

		$path = $parsedPath !== '' ? $parsedPath : $trimmed;

		if (str_starts_with($path, '/storage/')) {
			$path = substr($path, strlen('/storage/'));
		}

		return ltrim($path, '/');
	}
}
