<?php

namespace Pagify\PageBuilder\Http\Controllers\Admin;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use RuntimeException;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Services\EditorAccessTokenService;

class EditorSpaController extends Controller
{
	use AuthorizesRequests;

	public function __invoke(Request $request, EditorAccessTokenService $editorAccessToken): View
	{
		/** @var \Pagify\Core\Models\Admin|null $admin */
		$admin = $request->user('web');
		$pageId = (int) $request->query('pageId', 0);

		if ($admin === null) {
			$token = trim((string) $request->query('accessToken', ''));

			if ($token === '') {
				abort(403);
			}

			try {
				$claims = $editorAccessToken->verify($token);
			} catch (\InvalidArgumentException) {
				abort(403);
			}

			if (! $this->hasScope($claims, 'page:read')) {
				abort(403);
			}

			$claimPageId = isset($claims['page_id']) ? (int) $claims['page_id'] : null;
			if ($pageId > 0 && $claimPageId !== null && $claimPageId > 0 && $claimPageId !== $pageId) {
				abort(403);
			}

			return view('page-builder::editor-spa', [
				'webstudioBootstrap' => $this->resolveWebstudioBootstrap(),
			]);
		}

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

		return view('page-builder::editor-spa', [
			'webstudioBootstrap' => $this->resolveWebstudioBootstrap(),
		]);
	}

	/**
	 * @param array<string, mixed> $claims
	 */
	private function hasScope(array $claims, string $requiredScope): bool
	{
		$scopes = array_values(array_filter(array_map(
			static fn (mixed $scope): string => is_string($scope) ? trim($scope) : '',
			(array) ($claims['scopes'] ?? []),
		), static fn (string $scope): bool => $scope !== ''));

		return in_array($requiredScope, $scopes, true);
	}

	/**
	 * @return array{manifest: string, entry: string}
	 */
	private function resolveWebstudioBootstrap(): array
	{
		$assetsDir = public_path('build/page-builder/assets');

		$manifestFiles = glob($assetsDir . '/manifest-*.js') ?: [];
		$entryFiles = glob($assetsDir . '/entry.client-*.js') ?: [];

		usort($manifestFiles, static fn (string $left, string $right): int => filemtime($right) <=> filemtime($left));
		usort($entryFiles, static fn (string $left, string $right): int => filemtime($right) <=> filemtime($left));

		$manifestFile = $manifestFiles[0] ?? null;
		$entryFile = $entryFiles[0] ?? null;

		if ($manifestFile === null || $entryFile === null) {
			throw new RuntimeException('Webstudio client build assets are missing. Run modules/page-builder build pipeline.');
		}

		return [
			'manifest' => '/build/page-builder/assets/' . basename($manifestFile),
			'entry' => '/build/page-builder/assets/' . basename($entryFile),
		];
	}
}
