<?php

namespace Pagify\PageBuilder\Services;

use Illuminate\Support\Facades\DB;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Models\PageRevision;

class PageRevisionService
{
	public function __construct(private readonly PageDiffService $diffService)
	{
	}

	/**
	 * @param array<string, mixed>|null $beforeSnapshot
	 * @param array<string, mixed> $metadata
	 */
	public function snapshot(
		Page $page,
		string $action,
		?int $adminId = null,
		?array $beforeSnapshot = null,
		array $metadata = [],
	): PageRevision {
		return DB::transaction(function () use ($page, $action, $adminId, $beforeSnapshot, $metadata): PageRevision {
			$latestRevisionNo = (int) (PageRevision::query()->where('page_id', $page->id)->max('revision_no') ?? 0);
			$currentSnapshot = $this->snapshotFromPage($page);

			$baseSnapshot = $beforeSnapshot;
			if ($baseSnapshot === null) {
				$latestRevision = PageRevision::query()->where('page_id', $page->id)->latest('revision_no')->first();
				$baseSnapshot = (array) ($latestRevision?->snapshot_json ?? []);
			}

			$diff = $this->diffService->diff($baseSnapshot, $currentSnapshot);

			return PageRevision::query()->create([
				'page_id' => $page->id,
				'revision_no' => $latestRevisionNo + 1,
				'action' => $action,
				'snapshot_json' => $currentSnapshot,
				'diff_json' => $diff,
				'created_by_admin_id' => $adminId,
				'metadata_json' => $metadata,
			]);
		});
	}

	public function rollback(Page $page, PageRevision $targetRevision, ?int $adminId = null): Page
	{
		return DB::transaction(function () use ($page, $targetRevision, $adminId): Page {
			$beforeSnapshot = $this->snapshotFromPage($page);
			$targetSnapshot = (array) $targetRevision->snapshot_json;

			$page->fill([
				'title' => (string) ($targetSnapshot['title'] ?? $page->title),
				'slug' => (string) ($targetSnapshot['slug'] ?? $page->slug),
				'status' => (string) ($targetSnapshot['status'] ?? $page->status),
				'layout_json' => (array) ($targetSnapshot['layout'] ?? []),
				'seo_meta_json' => (array) ($targetSnapshot['seo'] ?? []),
			]);

			if ($page->status === 'published' && $page->published_at === null) {
				$page->published_at = now();
			}

			$page->save();

			$this->snapshot(
				page: $page->refresh(),
				action: 'rollback',
				adminId: $adminId,
				beforeSnapshot: $beforeSnapshot,
				metadata: [
					'rolled_back_from_revision_id' => $targetRevision->id,
					'rolled_back_from_revision_no' => $targetRevision->revision_no,
				],
			);

			return $page->refresh();
		});
	}

	/**
	 * @return array<string, mixed>
	 */
	public function snapshotFromPage(Page $page): array
	{
		return [
			'title' => $page->title,
			'slug' => $page->slug,
			'status' => $page->status,
			'layout' => (array) ($page->layout_json ?? []),
			'seo' => (array) ($page->seo_meta_json ?? []),
		];
	}
}
