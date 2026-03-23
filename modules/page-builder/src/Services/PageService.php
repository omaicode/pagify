<?php

namespace Pagify\PageBuilder\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Pagify\PageBuilder\Models\Page;

class PageService
{
	public function __construct(
		private readonly PageMediaUsageSyncService $pageMediaUsageSync,
		private readonly PageWsrePublisherService $pageWsrePublisher,
	) {
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public function create(array $payload, ?int $adminId = null): Page
	{
		return DB::transaction(function () use ($payload, $adminId): Page {
			$requestedHome = Arr::exists($payload, 'is_home') ? (bool) $payload['is_home'] : null;
			$shouldBeHome = $requestedHome ?? (Page::query()->exists() === false);
			$slug = $shouldBeHome ? '/' : (string) ($payload['slug'] ?? '');

			if ($shouldBeHome) {
				$this->releaseHomeSlugForAnotherPage();
			}

			$this->ensureSlugIsUnique($slug);

			$page = Page::query()->create([
				'title' => (string) ($payload['title'] ?? ''),
				'slug' => $slug,
				'is_home' => $shouldBeHome,
				'layout_json' => $this->resolveLayoutPayload($payload),
				'seo_meta_json' => (array) ($payload['seo_meta'] ?? []),
				'created_by_admin_id' => $adminId,
				'updated_by_admin_id' => $adminId,
			]);

			if ($shouldBeHome) {
				$this->setAsHomePage($page);
			}

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
			$requestedHome = Arr::exists($payload, 'is_home') ? (bool) $payload['is_home'] : null;
			$shouldBeHome = $requestedHome === true || (bool) $page->is_home;
			$nextSlug = $shouldBeHome ? '/' : (string) ($payload['slug'] ?? $page->slug);

			if ($shouldBeHome) {
				$this->releaseHomeSlugForAnotherPage((int) $page->id);
			}

			$this->ensureSlugIsUnique($nextSlug, $page->id);

			$page->fill([
				'title' => (string) ($payload['title'] ?? $page->title),
				'slug' => $nextSlug,
				'is_home' => $shouldBeHome,
				'layout_json' => $this->resolveLayoutPayload($payload, (array) ($page->layout_json ?? [])),
				'seo_meta_json' => (array) ($payload['seo_meta'] ?? $page->seo_meta_json ?? []),
				'updated_by_admin_id' => $adminId,
			]);

			$page->save();

			if ($shouldBeHome) {
				$this->setAsHomePage($page);
			}

			$updated = $page->refresh();

			if (Page::query()->where('is_home', true)->doesntExist()) {
				$updated->forceFill(['is_home' => true])->save();
				$updated = $updated->refresh();
			}

			$this->pageMediaUsageSync->syncForPage($updated);

			return $updated;
		});
	}

	/**
	 * @param array<string, mixed> $publishPayload
	 */
	public function publish(Page $page, ?int $adminId = null, array $publishPayload = []): Page
	{
		return DB::transaction(function () use ($page, $adminId, $publishPayload): Page {
			$page->forceFill([
				'updated_by_admin_id' => $adminId,
			])->save();

			$published = $page->refresh();
			$this->pageWsrePublisher->publish($published, $publishPayload);

			return $published;
		});
	}

	public function ensureHomePageExists(?int $adminId = null): Page
	{
		return DB::transaction(function () use ($adminId): Page {
			$existingHome = Page::query()->where('is_home', true)->orderBy('id')->first();
			if ($existingHome instanceof Page) {
				return $existingHome;
			}

			$existing = Page::query()
				->orderByRaw("CASE WHEN slug = '/' THEN 0 ELSE 1 END")
				->orderBy('id')
				->first();

			if ($existing instanceof Page) {
				$this->setAsHomePage($existing);

				return $existing;
			}

			$homePage = Page::query()->create([
				'title' => 'Home',
				'slug' => '/',
				'is_home' => true,
				'layout_json' => [],
				'seo_meta_json' => [],
				'created_by_admin_id' => $adminId,
				'updated_by_admin_id' => $adminId,
			]);

			$this->pageMediaUsageSync->syncForPage($homePage);

			return $homePage->refresh();
		});
	}

	private function setAsHomePage(Page $page): void
	{
		Page::query()->whereKeyNot($page->id)->where('is_home', true)->update(['is_home' => false]);
		$page->forceFill(['is_home' => true])->save();
	}

	private function releaseHomeSlugForAnotherPage(?int $promotedPageId = null): void
	{
		$currentHomeQuery = Page::query()->where('is_home', true);

		if ($promotedPageId !== null) {
			$currentHomeQuery->whereKeyNot($promotedPageId);
		}

		$currentHome = $currentHomeQuery->orderBy('id')->first();
		if (! $currentHome instanceof Page) {
			return;
		}

		if ((string) $currentHome->slug !== '/') {
			return;
		}

		$currentHome->forceFill([
			'slug' => $this->generateAvailableSlugForDemotedHome($currentHome),
		])->save();
	}

	private function generateAvailableSlugForDemotedHome(Page $page): string
	{
		$baseSlug = Str::slug((string) $page->title);

		if ($baseSlug === '' || $baseSlug === 'home') {
			$baseSlug = 'page-' . (string) $page->id;
		}

		$candidate = $baseSlug;
		$suffix = 1;

		while ($candidate === '/' || Page::query()->where('slug', $candidate)->whereKeyNot($page->id)->exists()) {
			$candidate = $baseSlug . '-' . $suffix;
			$suffix++;
		}

		return $candidate;
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
