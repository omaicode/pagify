<?php

namespace Pagify\PageBuilder\Http\Controllers\Admin;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Pagify\Core\Support\SiteContext;
use Pagify\Core\Services\AuditLogger;
use Pagify\Core\Services\FrontendThemeManagerService;
use Pagify\Core\Services\ThemeHelpers\AssetThemeHelper;
use Pagify\PageBuilder\Http\Requests\Admin\StorePageRequest;
use Pagify\PageBuilder\Http\Requests\Admin\UpdatePageRequest;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Services\EditorAccessTokenService;
use Pagify\PageBuilder\Services\PageService;

class PageController extends Controller
{
	use AuthorizesRequests;

	private const VIRTUAL_PROJECT_ID = 'pagify-local';

	public function __construct(
		private readonly PageService $pageService,
		private readonly AuditLogger $auditLogger,
		private readonly SiteContext $siteContext,
		private readonly FrontendThemeManagerService $themes,
		private readonly AssetThemeHelper $assetTheme,
		private readonly EditorAccessTokenService $editorAccessToken,
		private readonly Filesystem $files,
	) {
	}

	public function index(Request $request): Response
	{
		$this->authorize('viewAny', Page::class);
		return Inertia::render('PageBuilder/Editor', [
			'editor' => $this->editorPayload($request),
		]);
	}

	public function create(Request $request): RedirectResponse
	{
		$this->authorize('create', Page::class);

		return $this->redirectToEditor($request);
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

		return $this->redirectToEditor($request, $page)
			->with('status', __('page-builder::messages.page_created'));
	}

	public function edit(Request $request, Page $page): RedirectResponse
	{
		$this->authorize('update', $page);

		return $this->redirectToEditor($request, $page);
	}

	private function redirectToEditor(Request $request, ?Page $page = null): RedirectResponse
	{
		$activeThemeSlug = $this->themes->activeThemeForCurrentSite();
		$iframe = $this->iframeEditorPayload($request, $activeThemeSlug, $page);
		$editorSpaBaseUrl = route('page-builder.admin.editor.spa', ['path' => '/']);

		return redirect()->to((string) ($iframe['url'] ?? $editorSpaBaseUrl));
	}

	/**
	 * @return array<string, mixed>
	 */
	private function editorPayload(Request $request, ?Page $page = null): array
	{
		$editorConfig = (array) config('page-builder.editor', []);
		$activeThemeSlug = $this->themes->activeThemeForCurrentSite();
		$layouts = []; // Removed activeThemeLayouts call
		$canvasStyles = [];

		if ((bool) ($editorConfig['load_active_theme_styles'] ?? true)) {
			$canvasStyles[] = $this->assetTheme->url('css/main.css', $activeThemeSlug);
		}

		$previewUrl = $page !== null
			? route('page-builder.admin.pages.preview', $page)
			: null;

		return [
			'layouts' => $layouts,
			'default_layout' => $layouts[0]['path'] ?? '',
			'breakpoints' => array_values((array) config('page-builder.breakpoints', ['desktop', 'tablet', 'mobile'])),
			'active_theme' => $activeThemeSlug,
			'canvas_styles' => $canvasStyles,
			'preview_url' => $previewUrl,
			'iframe' => $this->iframeEditorPayload($request, $activeThemeSlug, $page),
		];
	}

	/**
	 * @return array{enabled: bool, url: string, origin: string, access_token: string, token_expires_at: ?string, token_refresh_url: string, message_namespace: string}
	 */
	private function iframeEditorPayload(Request $request, string $activeThemeSlug, ?Page $page = null): array
	{
		$editorUrl = route('page-builder.admin.editor.spa', ['path' => '/']);
		$origin = $request->getSchemeAndHttpHost();
		$initialPageId = $page?->id;

		if ($initialPageId === null) {
			$initialPageId = Page::query()->orderBy('id')->value('id');
		}

		$bootstrapProjectId = $initialPageId !== null
			? (string) $initialPageId
			: self::VIRTUAL_PROJECT_ID;

		/** @var \Pagify\Core\Models\Admin|null $admin */
		$admin = $request->user('web');
		$site = $this->siteContext->site();

		$issuedToken = $this->editorAccessToken->issue([
			'admin_id' => $admin?->id,
			'page_id' => $page?->id,
			'page_slug' => $page?->slug,
			'site_id' => $site?->id,
			'site_slug' => $site?->slug,
			'theme' => $activeThemeSlug,
			'scopes' => ['page:read', 'page:write', 'page:publish', 'media:read', 'media:write'],
		]);

		$query = [
			'accessToken' => $issuedToken['token'],
			'parentOrigin' => $request->getSchemeAndHttpHost(),
			'pageId' => $bootstrapProjectId,
		];

		if ($site?->id !== null) {
			$query['siteId'] = (string) $site->id;
		}

		$separator = str_contains($editorUrl, '?') ? '&' : '?';
		$iframeUrl = $editorUrl . $separator . http_build_query($query);

		return [
			'enabled' => true,
			'url' => $iframeUrl,
			'origin' => $origin,
			'access_token' => $issuedToken['token'],
			'token_expires_at' => $issuedToken['expires_at'],
			'token_refresh_url' => route('page-builder.api.v1.admin.editor.access-token', [], false),
			'message_namespace' => 'pagify:editor',
		];
	}

	/**
	 * @param array<string, mixed> $layout
	 * @return array{html: string, css: string}
	 */
	private function resolveEditorMarkupFromLayout(array $layout): array
	{
		$type = strtolower(trim((string) ($layout['type'] ?? '')));
		$webstudio = (array) ($layout['webstudio'] ?? []);

		if ($type !== 'webstudio' && $webstudio === []) {
			return ['html' => '', 'css' => ''];
		}

		$document = (array) ($webstudio['document'] ?? []);
		$html = is_string($webstudio['html'] ?? null)
			? (string) $webstudio['html']
			: (is_string($document['html'] ?? null) ? (string) $document['html'] : '');
		$css = is_string($webstudio['css'] ?? null)
			? trim((string) $webstudio['css'])
			: '';

		if ($css === '' && is_array($webstudio['styles'] ?? null)) {
			$styles = (array) $webstudio['styles'];
			$inlineCss = array_filter(array_map(static fn (mixed $item): string => is_string($item) ? trim($item) : '', $styles));
			$css = implode("\n", $inlineCss);
		}

		return ['html' => $html, 'css' => $css];
	}

	/**
	 * @return array<int, array{path: string, label: string}>
	 */
	private function manifestLayoutDefinitions(string $themePath): array
	{
		$manifestPath = $themePath . '/theme.json';

		if (! $this->files->exists($manifestPath)) {
			return [];
		}

		$decoded = json_decode((string) $this->files->get($manifestPath), true);

		if (! is_array($decoded)) {
			return [];
		}

		$rawLayouts = $decoded['layouts'] ?? null;

		if (! is_array($rawLayouts)) {
			return [];
		}

		$definitions = [];

		foreach ($rawLayouts as $key => $item) {
			$file = '';
			$label = '';

			if (is_array($item)) {
				$file = trim((string) ($item['file'] ?? ''));
				$label = trim((string) ($item['label'] ?? ''));
			} elseif (is_string($item)) {
				$file = trim($item);
				$label = is_string($key) ? trim($key) : '';
			}

			if ($file === '') {
				continue;
			}

			$normalizedFile = trim(str_replace('\\', '/', $file), '/');

			if (! str_starts_with($normalizedFile, 'layouts/')) {
				$normalizedFile = 'layouts/' . $normalizedFile;
			}

			$absolutePath = $themePath . '/' . str_replace('/', DIRECTORY_SEPARATOR, $normalizedFile);

			if (! $this->files->exists($absolutePath)) {
				continue;
			}

			$definitions[] = [
				'path' => $normalizedFile,
				'label' => $label !== '' ? $label : basename($normalizedFile),
			];
		}

		return array_values(array_unique($definitions, SORT_REGULAR));
	}

	/**
	 * @return array<int, array{href: string, media?: string}>
	 */
	private function extractPreviewSafeStyleLinksFromHtml(string $html): array
	{
		if (preg_match_all('/<link\b([^>]*)>/is', $html, $matches) < 1) {
			return [];
		}

		$styles = [];

		foreach ((array) ($matches[1] ?? []) as $rawAttributes) {
			$attributes = $this->parseHtmlAttributes((string) $rawAttributes);
			$rel = strtolower(trim((string) ($attributes['rel'] ?? '')));

			if ($rel === '' || ! str_contains($rel, 'stylesheet')) {
				continue;
			}

			$href = trim((string) ($attributes['href'] ?? ''));

			if ($href === '' || ! $this->isSafePreviewStyleUrl($href)) {
				continue;
			}

			$style = ['href' => $href];

			$media = trim((string) ($attributes['media'] ?? ''));
			if ($media !== '') {
				$style['media'] = $media;
			}

			$styles[] = $style;
		}

		return $styles;
	}

	private function editorLayoutHtmlFromTwig(string $rawTwig, string $label): string
	{
		$markup = preg_replace_callback(
			'/\{\%\s*include\s+[\"\']([^\"\']+)[\"\']\s*\%\}/',
			static fn (array $matches): string => '<section class="pbx-card" data-pbx-locked="true"><p class="pbx-caption">Included: ' . e((string) ($matches[1] ?? 'component')) . '</p></section>',
			$rawTwig,
		);

		$markup = preg_replace(
			'/\{\%\s*block\s+body\s*\%\}(.*?)\{\%\s*endblock\s*\%\}/s',
			'<main data-pbx-content-slot="true" class="pbx-layout-content"><section class="pbx-section"><h2 class="pbx-subheading">Editable content area</h2><p class="pbx-text">Drag, drop, add, remove, or edit blocks in this area.</p></section></main>',
			(string) $markup,
		);

		$markup = preg_replace('/\{\%[^\%]*\%\}/', '', (string) $markup);
		$markup = preg_replace('/\{\{[^\}]*\}\}/', '', (string) $markup);

		$body = '';
		if (preg_match('/<body[^>]*>(.*?)<\/body>/is', (string) $markup, $matches) === 1) {
			$body = trim((string) ($matches[1] ?? ''));
		} else {
			$body = trim((string) $markup);
		}

		if (! str_contains($body, 'data-pbx-content-slot="true"')) {
			$body .= '<main data-pbx-content-slot="true" class="pbx-layout-content"><section class="pbx-section"><h2 class="pbx-subheading">Editable content area</h2><p class="pbx-text">Drag, drop, add, remove, or edit blocks in this area.</p></section></main>';
		}

		$body = trim($body);
		if ($body === '') {
			return $this->defaultEditorLayoutHtml($label);
		}

		return '<div data-pbx-layout="' . e($label) . '">' . $body . '</div>';
	}

	private function defaultEditorLayoutHtml(string $label): string
	{
		return '<div data-pbx-layout="' . e($label) . '"><main data-pbx-content-slot="true" class="pbx-layout-content"><section class="pbx-section"><h2 class="pbx-subheading">Editable content area</h2><p class="pbx-text">Drag, drop, add, remove, or edit blocks in this area.</p></section></main></div>';
	}

	private function extractBodyFromHtml(string $html): string
	{
		if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches) !== 1) {
			return '';
		}

		return trim((string) ($matches[1] ?? ''));
	}

	private function extractContentSlotHtml(string $html): string
	{
		if (trim($html) === '' || ! class_exists(\DOMDocument::class)) {
			return '';
		}

		$dom = new \DOMDocument('1.0', 'UTF-8');
		$wrapped = '<!doctype html><html><body>' . $html . '</body></html>';

		$internalErrors = libxml_use_internal_errors(true);
		$loaded = $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();
		libxml_use_internal_errors($internalErrors);

		if (! $loaded) {
			return '';
		}

		$xpath = new \DOMXPath($dom);
		$nodes = $xpath->query('//*[@data-pbx-content-slot="true"]');

		if ($nodes === false || $nodes->length < 1) {
			return '';
		}

		$slot = $nodes->item(0);

		if (! $slot instanceof \DOMElement) {
			return '';
		}

		$content = '';

		foreach ($slot->childNodes as $child) {
			$content .= $dom->saveHTML($child);
		}

		return trim($content);
	}

	/**
	 * @return array<string, string>
	 */
	private function extractBodyAttributesFromHtml(string $html): array
	{
		return $this->extractTagAttributesFromHtml($html, 'body');
	}

	/**
	 * @return array<string, string>
	 */
	private function extractTagAttributesFromHtml(string $html, string $tag): array
	{
		if (preg_match('/<' . preg_quote($tag, '/') . '([^>]*)>/is', $html, $matches) !== 1) {
			return [];
		}

		$rawAttributes = trim((string) ($matches[1] ?? ''));

		if ($rawAttributes === '') {
			return [];
		}

		preg_match_all('/([a-zA-Z_:][a-zA-Z0-9_:\-.]*)\s*=\s*(["\'])(.*?)\2/s', $rawAttributes, $attributeMatches, PREG_SET_ORDER);

		$attributes = [];

		foreach ($attributeMatches as $attributeMatch) {
			$name = strtolower(trim((string) ($attributeMatch[1] ?? '')));
			$value = trim((string) ($attributeMatch[3] ?? ''));

			if ($name === '') {
				continue;
			}

			$attributes[$name] = $value;
		}

		return $attributes;
	}

	/**
	 * @return array<int, array{src: string, defer: bool, async: bool, type?: string}>
	 */
	private function extractPreviewSafeScriptsFromHtml(string $html): array
	{
		if (preg_match_all('/<script\b([^>]*)><\/script>/is', $html, $matches) < 1) {
			return [];
		}

		$scripts = [];

		foreach ((array) ($matches[1] ?? []) as $rawAttributes) {
			$attributes = $this->parseHtmlAttributes((string) $rawAttributes);
			$src = trim((string) ($attributes['src'] ?? ''));

			if ($src === '' || ! $this->isSafePreviewScriptUrl($src)) {
				continue;
			}

			$script = [
				'src' => $src,
				'defer' => array_key_exists('defer', $attributes),
				'async' => array_key_exists('async', $attributes),
			];

			$type = trim((string) ($attributes['type'] ?? ''));
			if ($type !== '') {
				$script['type'] = $type;
			}

			$scripts[] = $script;
		}

		return $scripts;
	}

	/**
	 * @return array<string, string>
	 */
	private function parseHtmlAttributes(string $rawAttributes): array
	{
		$raw = trim($rawAttributes);

		if ($raw === '') {
			return [];
		}

		preg_match_all('/([a-zA-Z_:][a-zA-Z0-9_:\-.]*)(?:\s*=\s*(["\'])(.*?)\2)?/s', $raw, $attributeMatches, PREG_SET_ORDER);

		$attributes = [];

		foreach ($attributeMatches as $attributeMatch) {
			$name = strtolower(trim((string) ($attributeMatch[1] ?? '')));
			$value = trim((string) ($attributeMatch[3] ?? ''));

			if ($name === '') {
				continue;
			}

			$attributes[$name] = $value;
		}

		return $attributes;
	}

	private function isSafePreviewScriptUrl(string $src): bool
	{
		$path = (string) parse_url($src, PHP_URL_PATH);

		if ($path === '') {
			return false;
		}

		if (! str_ends_with(strtolower($path), '.js')) {
			return false;
		}

		if (str_starts_with($path, '/theme-assets/')) {
			return true;
		}

		if (str_starts_with($path, '/build/')) {
			return true;
		}

		return false;
	}

	private function isSafePreviewStyleUrl(string $href): bool
	{
		$lower = strtolower($href);

		if (str_starts_with($lower, 'https://fonts.googleapis.com/')) {
			return true;
		}

		$path = (string) parse_url($href, PHP_URL_PATH);

		if ($path === '') {
			return false;
		}

		if (! str_ends_with(strtolower($path), '.css')) {
			return false;
		}

		if (str_starts_with($path, '/theme-assets/')) {
			return true;
		}

		if (str_starts_with($path, '/build/')) {
			return true;
		}

		return false;
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

		return $this->redirectToEditor($request, $updated)->with('status', __('page-builder::messages.page_updated'));
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

		return $this->redirectToEditor($request, $page)->with('status', __('page-builder::messages.page_published'));
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

}
