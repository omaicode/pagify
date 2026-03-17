<?php

namespace Pagify\PageBuilder\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Pagify\PageBuilder\Models\Page;

class PageService
{
	public function __construct(
		private readonly PageMediaUsageSyncService $pageMediaUsageSync,
	) {
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public function create(array $payload, ?int $adminId = null): Page
	{
		return DB::transaction(function () use ($payload, $adminId): Page {
			$slug = (string) ($payload['slug'] ?? '');
			$this->ensureSlugIsUnique($slug);

			$page = Page::query()->create([
				'title' => (string) ($payload['title'] ?? ''),
				'slug' => $slug,
				'status' => (string) ($payload['status'] ?? 'draft'),
				'layout_json' => $this->resolveLayoutPayload($payload),
				'seo_meta_json' => (array) ($payload['seo_meta'] ?? []),
				'published_at' => ($payload['status'] ?? 'draft') === 'published' ? now() : null,
				'created_by_admin_id' => $adminId,
				'updated_by_admin_id' => $adminId,
			]);

			$this->pageMediaUsageSync->syncForPage($page);

			return $page->refresh();
		});
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public function update(Page $page, array $payload, ?int $adminId = null): Page
	{
		return DB::transaction(function () use ($page, $payload, $adminId): Page {
			$nextSlug = (string) ($payload['slug'] ?? $page->slug);
			$this->ensureSlugIsUnique($nextSlug, $page->id);

			$page->fill([
				'title' => (string) ($payload['title'] ?? $page->title),
				'slug' => $nextSlug,
				'status' => (string) ($payload['status'] ?? $page->status),
				'layout_json' => $this->resolveLayoutPayload($payload, (array) ($page->layout_json ?? [])),
				'seo_meta_json' => (array) ($payload['seo_meta'] ?? $page->seo_meta_json ?? []),
				'updated_by_admin_id' => $adminId,
			]);

			if ($page->status === 'published' && $page->published_at === null) {
				$page->published_at = now();
			}

			$page->save();

			$updated = $page->refresh();
			$this->pageMediaUsageSync->syncForPage($updated);

			return $updated;
		});
	}

	public function publish(Page $page, ?int $adminId = null): Page
	{
		return DB::transaction(function () use ($page, $adminId): Page {
			$page->forceFill([
				'status' => 'published',
				'published_at' => now(),
				'updated_by_admin_id' => $adminId,
			])->save();

			return $page->refresh();
		});
	}

	public function delete(Page $page): void
	{
		DB::transaction(function () use ($page): void {
			$this->pageMediaUsageSync->clearForPage($page);
			$page->forceDelete();
		});
	}

	private function ensureSlugIsUnique(string $slug, ?int $ignorePageId = null): void
	{
		$query = Page::query()->where('slug', $slug);

		if ($ignorePageId !== null) {
			$query->whereKeyNot($ignorePageId);
		}

		if ($query->exists()) {
			throw ValidationException::withMessages([
				'slug' => __('The slug has already been taken for this site.'),
			]);
		}
	}

	/**
	 * @param array<string, mixed> $payload
	 * @param array<string, mixed> $fallback
	 * @return array<string, mixed>
	 */
	private function resolveLayoutPayload(array $payload, array $fallback = []): array
	{
		$layout = Arr::get($payload, 'layout');

		if (is_array($layout)) {
			return $this->normalizeWebstudioLayout($layout);
		}

		return is_array($fallback) ? $this->normalizeWebstudioLayout($fallback) : [];
	}

	/**
	 * @param array<string, mixed> $layout
	 * @return array<string, mixed>
	 */
	private function normalizeWebstudioLayout(array $layout): array
	{
		if (array_key_exists('grapes', $layout)) {
			throw ValidationException::withMessages([
				'layout' => __('Legacy GrapesJS payload is no longer supported. Please reload editor and save again using Webstudio format.'),
			]);
		}

		$type = strtolower(trim((string) ($layout['type'] ?? 'webstudio')));

		if ($type !== 'webstudio') {
			throw ValidationException::withMessages([
				'layout.type' => __('Only webstudio layout type is supported.'),
			]);
		}

		$webstudio = (array) ($layout['webstudio'] ?? []);
		$document = (array) ($webstudio['document'] ?? []);

		$html = is_string($webstudio['html'] ?? null)
			? trim((string) $webstudio['html'])
			: (is_string($document['html'] ?? null) ? trim((string) $document['html']) : '');

		$css = is_string($webstudio['css'] ?? null)
			? (string) $webstudio['css']
			: '';

		if ($css === '' && is_array($webstudio['styles'] ?? null)) {
			$css = implode("\n", array_filter(array_map(static fn (mixed $item): string => is_string($item) ? trim($item) : '', (array) $webstudio['styles'])));
		}

		$styles = is_array($webstudio['styles'] ?? null)
			? array_values(array_filter(array_map(static fn (mixed $item): string => is_string($item) ? $item : '', (array) $webstudio['styles']), static fn (string $item): bool => trim($item) !== ''))
			: ($css !== '' ? [$css] : []);

		$assets = is_array($webstudio['assets'] ?? null) ? (array) $webstudio['assets'] : [];
		$meta = is_array($webstudio['meta'] ?? null) ? (array) $webstudio['meta'] : [];

		$normalized = [
			'type' => 'webstudio',
			'theme_layout' => is_string($layout['theme_layout'] ?? null) ? (string) $layout['theme_layout'] : '',
			'webstudio' => [
				'html' => $html,
				'css' => $css,
				'document' => [
					'html' => $html,
				],
				'styles' => $styles,
				'assets' => $assets,
				'meta' => $meta,
				'updated_at' => is_string($webstudio['updated_at'] ?? null) ? (string) $webstudio['updated_at'] : now()->toIso8601String(),
			],
		];

		return $normalized;
	}
}
