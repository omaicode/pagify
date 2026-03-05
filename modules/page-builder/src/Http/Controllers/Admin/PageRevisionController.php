<?php

namespace Pagify\PageBuilder\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Pagify\Core\Services\AuditLogger;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Models\PageRevision;
use Pagify\PageBuilder\Services\PageDiffService;
use Pagify\PageBuilder\Services\PageRevisionService;

class PageRevisionController extends Controller
{
	use AuthorizesRequests;

	public function __construct(
		private readonly PageRevisionService $revisionService,
		private readonly PageDiffService $diffService,
		private readonly AuditLogger $auditLogger,
	) {
	}

	public function index(Request $request, Page $page): Response
	{
		$this->authorize('viewRevisions', $page);

		$revisions = PageRevision::query()->where('page_id', $page->id)->latest('revision_no')->get();

		$leftRevision = null;
		$rightRevision = null;
		$diff = ['changed' => false, 'changes' => []];

		if ($revisions->count() >= 2) {
			$leftRevisionId = (int) ($request->integer('left_revision_id') ?: $revisions->get(1)->id);
			$rightRevisionId = (int) ($request->integer('right_revision_id') ?: $revisions->first()->id);

			$leftRevision = $revisions->firstWhere('id', $leftRevisionId) ?? $revisions->get(1);
			$rightRevision = $revisions->firstWhere('id', $rightRevisionId) ?? $revisions->first();

			if ($leftRevision !== null && $rightRevision !== null) {
				$diff = $this->diffService->diff(
					(array) ($leftRevision->snapshot_json ?? []),
					(array) ($rightRevision->snapshot_json ?? []),
				);
			}
		}

		return Inertia::render('PageBuilder/Pages/Revisions/Index', [
			'page' => [
				'id' => $page->id,
				'title' => $page->title,
				'slug' => $page->slug,
			],
			'revisions' => $revisions->map(static fn (PageRevision $revision): array => [
				'id' => $revision->id,
				'revision_no' => $revision->revision_no,
				'action' => $revision->action,
				'created_at' => $revision->created_at?->toDateTimeString(),
			])->values()->all(),
			'leftRevision' => $leftRevision === null ? null : [
				'id' => $leftRevision->id,
				'revision_no' => $leftRevision->revision_no,
			],
			'rightRevision' => $rightRevision === null ? null : [
				'id' => $rightRevision->id,
				'revision_no' => $rightRevision->revision_no,
			],
			'diff' => $diff,
			'routes' => [
				'compare' => route('page-builder.admin.pages.revisions.index', $page),
				'rollbackBase' => route('page-builder.admin.pages.revisions.rollback', [$page, 0]),
				'pageEdit' => route('page-builder.admin.pages.edit', $page),
			],
		]);
	}

	public function rollback(Request $request, Page $page, PageRevision $revision): RedirectResponse
	{
		$this->authorize('rollbackRevision', $page);

		if ($revision->page_id !== $page->id) {
			abort(404);
		}

		/** @var \Pagify\Core\Models\Admin|null $admin */
		$admin = $request->user('web');

		$this->revisionService->rollback($page, $revision, $admin?->id);

		$this->auditLogger->log(
			action: 'page_builder.page.rollback',
			entityType: Page::class,
			entityId: $page->id,
			metadata: [
				'rolled_back_to_revision_id' => $revision->id,
				'rolled_back_to_revision_no' => $revision->revision_no,
			],
		);

		return redirect()->route('page-builder.admin.pages.revisions.index', $page)->with('status', __('page-builder::messages.rollback_completed'));
	}
}
