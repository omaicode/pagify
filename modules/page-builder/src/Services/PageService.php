<?php

namespace Pagify\PageBuilder\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Models\PageTemplate;

class PageService
{
	public function __construct(
		private readonly PageRevisionService $revisionService,
		private readonly PageSnapshotService $snapshotService,
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

			$this->revisionService->snapshot($page, 'created', $adminId);

			if ($page->status === 'published') {
				$this->snapshotService->refresh($page);
			}

			return $page->refresh();
		});
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public function update(Page $page, array $payload, ?int $adminId = null): Page
	{
		return DB::transaction(function () use ($page, $payload, $adminId): Page {
			$beforeSnapshot = $this->revisionService->snapshotFromPage($page);

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
			$this->revisionService->snapshot($updated, 'updated', $adminId, $beforeSnapshot);

			if ($updated->status === 'published') {
				$this->snapshotService->refresh($updated);
			}

			return $updated;
		});
	}

	public function publish(Page $page, ?int $adminId = null): Page
	{
		return DB::transaction(function () use ($page, $adminId): Page {
			$beforeSnapshot = $this->revisionService->snapshotFromPage($page);

			$page->forceFill([
				'status' => 'published',
				'published_at' => now(),
				'updated_by_admin_id' => $adminId,
			])->save();

			$published = $page->refresh();
			$this->snapshotService->refresh($published);
			$this->revisionService->snapshot($published, 'published', $adminId, $beforeSnapshot);

			return $published;
		});
	}

	public function delete(Page $page): void
	{
		$page->delete();
	}

	public function resolveTemplate(?string $templateSlug): ?array
	{
		if ($templateSlug === null || trim($templateSlug) === '') {
			return null;
		}

		$template = PageTemplate::query()
			->where('slug', $templateSlug)
			->where('is_active', true)
			->first();

		if ($template !== null) {
			return (array) ($template->schema_json ?? []);
		}

		foreach ((array) config('page-builder.default_page_templates', []) as $defaultTemplate) {
			if (! is_array($defaultTemplate)) {
				continue;
			}

			if ((string) ($defaultTemplate['slug'] ?? '') !== $templateSlug) {
				continue;
			}

			return (array) ($defaultTemplate['schema_json'] ?? []);
		}

		return null;
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
			return $layout;
		}

		$templateSlug = Arr::get($payload, 'template_slug');
		$templateLayout = $this->resolveTemplate(is_string($templateSlug) ? $templateSlug : null);

		if (is_array($templateLayout)) {
			return $templateLayout;
		}

		return $fallback;
	}
}
