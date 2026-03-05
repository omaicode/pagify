<?php

namespace Pagify\PageBuilder\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Pagify\Core\Services\AuditLogger;
use Pagify\PageBuilder\Http\Requests\Admin\StorePageRequest;
use Pagify\PageBuilder\Http\Requests\Admin\UpdatePageRequest;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Models\PageTemplate;
use Pagify\PageBuilder\Models\SectionTemplate;
use Pagify\PageBuilder\Services\BlockRegistryService;
use Pagify\PageBuilder\Services\PageService;

class PageController extends Controller
{
	use AuthorizesRequests;

	public function __construct(
		private readonly PageService $pageService,
		private readonly BlockRegistryService $blockRegistry,
		private readonly AuditLogger $auditLogger,
	) {
	}

	public function index(): Response
	{
		$this->authorize('viewAny', Page::class);

		$pages = Page::query()->latest('id')->paginate(20);

		$pages->setCollection(
			$pages->getCollection()->map(static fn (Page $page): array => [
				'id' => $page->id,
				'title' => $page->title,
				'slug' => $page->slug,
				'status' => $page->status,
				'published_at' => $page->published_at?->toDateTimeString(),
				'updated_at' => $page->updated_at?->toDateTimeString(),
				'routes' => [
					'edit' => route('page-builder.admin.pages.edit', $page),
					'destroy' => route('page-builder.admin.pages.destroy', $page),
				],
			])
		);

		return Inertia::render('PageBuilder/Pages/Index', [
			'pages' => $pages,
			'routes' => [
				'create' => route('page-builder.admin.pages.create'),
			],
		]);
	}

	public function create(Request $request): Response
	{
		$this->authorize('create', Page::class);

		$templateSlug = $request->query('template');
		$templateLayout = is_string($templateSlug) ? $this->pageService->resolveTemplate($templateSlug) : null;

		return Inertia::render('PageBuilder/Pages/Create', [
			'editor' => [
				'blocks' => $this->blockRegistry->all(),
				'breakpoints' => array_values((array) config('page-builder.breakpoints', ['desktop', 'tablet', 'mobile'])),
			],
			'startup' => [
				'template_slug' => $templateSlug,
				'layout' => $templateLayout,
			],
			'templates' => $this->templatePayload(),
			'sections' => $this->sectionPayload(),
			'routes' => [
				'store' => route('page-builder.admin.pages.store'),
				'index' => route('page-builder.admin.pages.index'),
				'storeSectionTemplate' => route('page-builder.admin.library.sections.store'),
				'storePageTemplate' => route('page-builder.admin.library.templates.store'),
			],
		]);
	}

	public function store(StorePageRequest $request): RedirectResponse
	{
		$this->authorize('create', Page::class);

		/** @var \Pagify\Core\Models\Admin|null $admin */
		$admin = $request->user('web');

		$page = $this->pageService->create($request->validated(), $admin?->id);

		$this->auditLogger->log(
			action: 'page_builder.page.created',
			entityType: Page::class,
			entityId: $page->id,
			metadata: ['slug' => $page->slug],
		);

		return redirect()
			->route('page-builder.admin.pages.edit', $page)
			->with('status', __('page-builder::messages.page_created'));
	}

	public function edit(Page $page): Response
	{
		$this->authorize('update', $page);

		return Inertia::render('PageBuilder/Pages/Edit', [
			'page' => [
				'id' => $page->id,
				'title' => $page->title,
				'slug' => $page->slug,
				'status' => $page->status,
				'layout' => (array) ($page->layout_json ?? []),
				'seo_meta' => (array) ($page->seo_meta_json ?? []),
				'published_at' => $page->published_at?->toDateTimeString(),
			],
			'editor' => [
				'blocks' => $this->blockRegistry->all(),
				'breakpoints' => array_values((array) config('page-builder.breakpoints', ['desktop', 'tablet', 'mobile'])),
			],
			'templates' => $this->templatePayload(),
			'sections' => $this->sectionPayload(),
			'routes' => [
				'update' => route('page-builder.admin.pages.update', $page),
				'destroy' => route('page-builder.admin.pages.destroy', $page),
				'publish' => route('page-builder.admin.pages.publish', $page),
				'revisions' => route('page-builder.admin.pages.revisions.index', $page),
				'index' => route('page-builder.admin.pages.index'),
				'storeSectionTemplate' => route('page-builder.admin.library.sections.store'),
				'storePageTemplate' => route('page-builder.admin.library.templates.store'),
			],
		]);
	}

	public function update(UpdatePageRequest $request, Page $page): RedirectResponse
	{
		$this->authorize('update', $page);

		/** @var \Pagify\Core\Models\Admin|null $admin */
		$admin = $request->user('web');
		$updated = $this->pageService->update($page, $request->validated(), $admin?->id);

		$this->auditLogger->log(
			action: 'page_builder.page.updated',
			entityType: Page::class,
			entityId: $updated->id,
			metadata: ['slug' => $updated->slug],
		);

		return redirect()->route('page-builder.admin.pages.edit', $updated)->with('status', __('page-builder::messages.page_updated'));
	}

	public function publish(Request $request, Page $page): RedirectResponse
	{
		$this->authorize('publish', $page);

		/** @var \Pagify\Core\Models\Admin|null $admin */
		$admin = $request->user('web');
		$this->pageService->publish($page, $admin?->id);

		$this->auditLogger->log(
			action: 'page_builder.page.published',
			entityType: Page::class,
			entityId: $page->id,
			metadata: ['slug' => $page->slug],
		);

		return redirect()->route('page-builder.admin.pages.edit', $page)->with('status', __('page-builder::messages.page_published'));
	}

	public function destroy(Page $page): RedirectResponse
	{
		$this->authorize('delete', $page);

		$pageId = $page->id;
		$slug = $page->slug;
		$this->pageService->delete($page);

		$this->auditLogger->log(
			action: 'page_builder.page.deleted',
			entityType: Page::class,
			entityId: $pageId,
			metadata: ['slug' => $slug],
		);

		return redirect()->route('page-builder.admin.pages.index')->with('status', __('page-builder::messages.page_deleted'));
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function templatePayload(): array
	{
		$defaultTemplates = collect((array) config('page-builder.default_page_templates', []))
			->filter(static fn (mixed $item): bool => is_array($item))
			->map(static fn (array $item): array => [
				'id' => 'default:' . ($item['slug'] ?? ''),
				'slug' => (string) ($item['slug'] ?? ''),
				'name' => (string) ($item['name'] ?? ($item['slug'] ?? 'Template')),
				'category' => (string) ($item['category'] ?? 'general'),
				'description' => (string) ($item['description'] ?? ''),
				'schema' => (array) ($item['schema_json'] ?? []),
				'source' => 'default',
			]);

		$dbTemplates = PageTemplate::query()->where('is_active', true)->latest('id')->get()
			->map(static fn (PageTemplate $template): array => [
				'id' => $template->id,
				'slug' => $template->slug,
				'name' => $template->name,
				'category' => $template->category,
				'description' => $template->description,
				'schema' => (array) ($template->schema_json ?? []),
				'source' => 'site',
			]);

		return $defaultTemplates->concat($dbTemplates)->values()->all();
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private function sectionPayload(): array
	{
		return SectionTemplate::query()->where('is_active', true)->latest('id')->get()
			->map(static fn (SectionTemplate $template): array => [
				'id' => $template->id,
				'slug' => $template->slug,
				'name' => $template->name,
				'schema' => (array) ($template->schema_json ?? []),
			])
			->values()
			->all();
	}
}
