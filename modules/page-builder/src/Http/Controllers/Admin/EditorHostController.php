<?php

namespace Pagify\PageBuilder\Http\Controllers\Admin;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Pagify\PageBuilder\Models\Page;

class EditorHostController extends Controller
{
	use AuthorizesRequests;

	public function __construct(
		private readonly Filesystem $files,
	) {
	}

	public function __invoke(Request $request): View
	{
		/** @var \Pagify\Core\Models\Admin|null $admin */
		$admin = $request->user('web');
		$pageId = (int) $request->query('pageId', 0);

		if ($pageId > 0) {
			$page = Page::query()->find($pageId);

			if ($page !== null) {
				$this->authorize('update', $page);
			} elseif (! $admin?->can('page-builder.page.update')) {
				abort(403);
			}
		} elseif (! $admin?->can('page-builder.page.create') && ! $admin?->can('page-builder.page.viewAny')) {
			abort(403);
		}

		$manifestPath = public_path('build/page-builder/.vite/manifest.json');
		$entryFile = '';

		if ($this->files->exists($manifestPath)) {
			$manifest = json_decode((string) $this->files->get($manifestPath), true);

			if (is_array($manifest)) {
				$entry = $manifest['resources/js/editor-host.js'] ?? null;
				$entryFile = is_array($entry) ? (string) ($entry['file'] ?? '') : '';
			}
		}

		$bootPayload = [
			'accessToken' => (string) $request->query('accessToken', ''),
			'pageId' => $request->query('pageId'),
			'siteId' => $request->query('siteId'),
			'theme' => (string) $request->query('theme', ''),
			'mode' => (string) $request->query('mode', 'page-builder'),
			'runtimeMode' => (string) config('page-builder.webstudio_iframe.runtime_mode', 'upstream-embedded'),
			'upstreamBuilderUrl' => (string) config('page-builder.webstudio_iframe.upstream_url', ''),
			'parentOrigin' => (string) $request->query('parentOrigin', ''),
			'contractUrl' => route('page-builder.api.v1.admin.editor.contract'),
			'tokenVerifyUrl' => route('page-builder.api.v1.admin.editor.verify-token'),
			'builderDataUrl' => route('page-builder.api.v1.admin.editor.builder-data'),
			'mediaAssetsUrl' => route('page-builder.api.v1.admin.editor.media.assets'),
			'mediaUploadUrl' => route('page-builder.api.v1.admin.editor.media.assets.upload'),
		];

		return view('page-builder::editor-host', [
			'entryScriptUrl' => $entryFile !== '' ? asset('build/page-builder/' . $entryFile) : '',
			'bootPayload' => $bootPayload,
		]);
	}
}
