<?php

namespace Pagify\PageBuilder\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Pagify\PageBuilder\Http\Requests\Admin\StorePageTemplateRequest;
use Pagify\PageBuilder\Http\Requests\Admin\StoreSectionTemplateRequest;
use Pagify\PageBuilder\Models\PageTemplate;
use Pagify\PageBuilder\Models\SectionTemplate;

class PageLibraryController extends Controller
{
	use AuthorizesRequests;

	public function storeSection(StoreSectionTemplateRequest $request): RedirectResponse
	{
		$this->authorize('create', SectionTemplate::class);

		/** @var \Pagify\Core\Models\Admin|null $admin */
		$admin = $request->user('web');

		SectionTemplate::query()->updateOrCreate(
			['slug' => (string) $request->input('slug')],
			[
				'name' => (string) $request->input('name'),
				'schema_json' => (array) $request->input('schema', []),
				'is_active' => true,
				'created_by_admin_id' => $admin?->id,
			]
		);

		return back()->with('status', __('page-builder::messages.section_template_saved'));
	}

	public function storePageTemplate(StorePageTemplateRequest $request): RedirectResponse
	{
		$this->authorize('create', PageTemplate::class);

		/** @var \Pagify\Core\Models\Admin|null $admin */
		$admin = $request->user('web');

		PageTemplate::query()->updateOrCreate(
			['slug' => (string) $request->input('slug')],
			[
				'name' => (string) $request->input('name'),
				'category' => $request->filled('category') ? (string) $request->input('category') : null,
				'description' => $request->filled('description') ? (string) $request->input('description') : null,
				'schema_json' => (array) $request->input('schema', []),
				'is_active' => true,
				'created_by_admin_id' => $admin?->id,
			]
		);

		return back()->with('status', __('page-builder::messages.page_template_saved'));
	}
}
