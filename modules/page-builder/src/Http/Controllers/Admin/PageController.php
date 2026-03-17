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
use Pagify\Core\Services\FrontendThemeTwigEngine;
use Pagify\Core\Services\ThemeHelpers\AssetThemeHelper;
use Pagify\PageBuilder\Http\Requests\Admin\StorePageRequest;
use Pagify\PageBuilder\Http\Requests\Admin\UpdatePageRequest;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Models\PageTemplate;
use Pagify\PageBuilder\Models\SectionTemplate;
use Pagify\PageBuilder\Services\BlockRegistryService;
use Pagify\PageBuilder\Services\EditorAccessTokenService;
use Pagify\PageBuilder\Services\PageService;

class PageController extends Controller
{
	use AuthorizesRequests;

	public function __construct(
		private readonly PageService $pageService,
		private readonly BlockRegistryService $blockRegistry,
		private readonly AuditLogger $auditLogger,
		private readonly SiteContext $siteContext,
		private readonly FrontendThemeManagerService $themes,
		private readonly FrontendThemeTwigEngine $twigEngine,
		private readonly AssetThemeHelper $assetTheme,
		private readonly EditorAccessTokenService $editorAccessToken,
		private readonly Filesystem $files,
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
			'editor' => $this->editorPayload($request),
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
		$request = request();

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
			'editor' => $this->editorPayload($request, $page),
			'templates' => $this->templatePayload(),
			'sections' => $this->sectionPayload(),
			'routes' => [
				'update' => route('page-builder.admin.pages.update', $page),
				'destroy' => route('page-builder.admin.pages.destroy', $page),
				'publish' => route('page-builder.admin.pages.publish', $page),
				'index' => route('page-builder.admin.pages.index'),
				'storeSectionTemplate' => route('page-builder.admin.library.sections.store'),
				'storePageTemplate' => route('page-builder.admin.library.templates.store'),
			],
		]);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function editorPayload(Request $request, ?Page $page = null): array
	{
		$allBlocks = $this->blockRegistry->all();
		$editorConfig = (array) config('page-builder.editor', []);
		$simplifiedMode = (bool) ($editorConfig['simplified_mode'] ?? true);
		$primaryBlockKeys = array_values(array_filter(array_map(
			static fn (mixed $key): string => trim((string) $key),
			(array) ($editorConfig['primary_blocks'] ?? []),
		), static fn (string $key): bool => $key !== ''));

		$blocks = $this->sortBlocksForEditor($allBlocks, $primaryBlockKeys);
		$activeThemeSlug = $this->themes->activeThemeForCurrentSite();
		$layouts = $this->activeThemeLayouts($activeThemeSlug);
		$canvasStyles = [];

		if ((bool) ($editorConfig['load_active_theme_styles'] ?? true)) {
			$canvasStyles[] = $this->assetTheme->url('css/main.css', $activeThemeSlug);
		}

		$previewUrl = $page !== null
			? route('page-builder.admin.pages.preview', $page)
			: null;

		return [
			'blocks' => $blocks,
			'layouts' => $layouts,
			'default_layout' => $layouts[0]['path'] ?? '',
			'breakpoints' => array_values((array) config('page-builder.breakpoints', ['desktop', 'tablet', 'mobile'])),
			'simple_mode' => $simplifiedMode,
			'primary_block_keys' => $primaryBlockKeys,
			'active_theme' => $activeThemeSlug,
			'canvas_styles' => $canvasStyles,
			'preview_url' => $previewUrl,
			'iframe' => $this->iframeEditorPayload($request, $activeThemeSlug, $page),
		];
	}

	/**
	 * @return array{enabled: bool, url: string, origin: string, access_token: string, token_expires_at: ?string, token_refresh_url: string, token_verify_url: string, contract_url: string, message_namespace: string}
	 */
	private function iframeEditorPayload(Request $request, string $activeThemeSlug, ?Page $page = null): array
	{
		$config = (array) config('page-builder.webstudio_iframe', []);
		$enabled = (bool) ($config['enabled'] ?? false);
		$editorUrl = trim((string) ($config['url'] ?? ''));

		if (! $enabled || $editorUrl === '') {
			return [
				'enabled' => false,
				'url' => '',
				'origin' => '',
				'access_token' => '',
				'token_expires_at' => null,
				'token_refresh_url' => '',
				'token_verify_url' => '',
				'contract_url' => '',
				'message_namespace' => 'pagify:editor',
			];
		}

		$origin = trim((string) ($config['origin'] ?? ''));
		if ($origin === '') {
			$parsedScheme = (string) parse_url($editorUrl, PHP_URL_SCHEME);
			$parsedHost = (string) parse_url($editorUrl, PHP_URL_HOST);
			$parsedPort = parse_url($editorUrl, PHP_URL_PORT);

			if ($parsedScheme !== '' && $parsedHost !== '') {
				$origin = $parsedScheme . '://' . $parsedHost . ($parsedPort !== null ? ':' . $parsedPort : '');
			}
		}

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
			'mode' => 'page-builder',
			'theme' => $activeThemeSlug,
			'accessToken' => $issuedToken['token'],
		];

		if ($page !== null) {
			$query['pageId'] = (string) $page->id;
		}

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
			'token_refresh_url' => route('page-builder.api.v1.admin.editor.access-token'),
			'token_verify_url' => route('page-builder.api.v1.admin.editor.verify-token'),
			'contract_url' => route('page-builder.api.v1.admin.editor.contract'),
			'message_namespace' => 'pagify:editor',
		];
	}

	public function preview(Page $page): HttpResponse
	{
		$this->authorize('view', $page);

		$layout = (array) ($page->layout_json ?? []);
		$editorMarkup = $this->resolveEditorMarkupFromLayout($layout);
		$editorHtml = $editorMarkup['html'];
		$editorCss = $editorMarkup['css'];

		$content = $this->extractContentSlotHtml($editorHtml);

		if ($content === '') {
			$content = trim($editorHtml);
		}

		if ($content === '') {
			$content = '<section class="pbx-section"><h2 class="pbx-subheading">Preview is empty</h2><p class="pbx-text">Save content in editor, then open live preview again.</p></section>';
		}

		$seo = (array) ($page->seo_meta_json ?? []);
		$title = e((string) ($seo['title'] ?? $page->title));
		$description = e((string) ($seo['description'] ?? ''));
		$canonical = e((string) ($seo['canonical_url'] ?? ''));
		$ogImage = e((string) ($seo['og_image'] ?? ''));
		$jsonLd = $seo['json_ld'] ?? null;
		$jsonLdString = is_array($jsonLd) ? json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

		$headParts = [
			"<title>{$title}</title>",
			$description !== '' ? "<meta name=\"description\" content=\"{$description}\">" : '',
			$canonical !== '' ? "<link rel=\"canonical\" href=\"{$canonical}\">" : '',
			$ogImage !== '' ? "<meta property=\"og:image\" content=\"{$ogImage}\">" : '',
			is_string($jsonLdString) ? '<script type="application/ld+json">' . $jsonLdString . '</script>' : '',
			$editorCss !== '' ? '<style>' . $editorCss . '</style>' : '',
			'<meta name="robots" content="noindex,nofollow">',
		];

		$head = implode("\n", array_filter($headParts));

		$rendered = $this->twigEngine->render($this->themes->viewPathsForCurrentSite(), 'pages/home.twig', [
			'page' => $page,
			'head' => $head,
			'content' => $content,
			'locale' => app()->getLocale(),
			'request_path' => $page->slug,
			'admin_prefix' => trim((string) config('app.admin_url_prefix', 'admin'), '/'),
		]);

		if (is_string($rendered) && trim($rendered) !== '') {
			return response($rendered, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
		}

		return response($content, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
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
	 * @param array<int, array<string, mixed>> $blocks
	 * @param array<int, string> $primaryBlockKeys
	 * @return array<int, array<string, mixed>>
	 */
	private function sortBlocksForEditor(array $blocks, array $primaryBlockKeys): array
	{
		if ($primaryBlockKeys === []) {
			return $blocks;
		}

		$priority = array_flip($primaryBlockKeys);
		$sorted = $blocks;

		usort($sorted, static function (array $left, array $right) use ($priority): int {
			$leftKey = (string) ($left['key'] ?? '');
			$rightKey = (string) ($right['key'] ?? '');
			$leftRank = array_key_exists($leftKey, $priority) ? (int) $priority[$leftKey] : PHP_INT_MAX;
			$rightRank = array_key_exists($rightKey, $priority) ? (int) $priority[$rightKey] : PHP_INT_MAX;

			if ($leftRank === $rightRank) {
				return strcmp((string) ($left['label'] ?? $leftKey), (string) ($right['label'] ?? $rightKey));
			}

			return $leftRank <=> $rightRank;
		});

		return $sorted;
	}

	/**
	 * @return array<int, array{path: string, label: string, editor_html: string, html_attributes: array<string, string>, body_attributes: array<string, string>, preview_styles: array<int, array<string, mixed>>, preview_scripts: array<int, array<string, mixed>>}>
	 */
	private function activeThemeLayouts(string $activeThemeSlug): array
	{
		$themesBasePath = trim((string) config('core.frontend_ui.themes_base_path', 'themes/main'), '/');
		$themePath = base_path($themesBasePath . '/' . $activeThemeSlug);
		$layoutDefinitions = $this->manifestLayoutDefinitions($themePath);

		$layoutFiles = collect($layoutDefinitions)
			->map(function (array $layoutDefinition) use ($themePath): array {
				$template = (string) ($layoutDefinition['path'] ?? 'layouts/app.twig');
				$label = (string) ($layoutDefinition['label'] ?? basename($template));
				$absoluteTemplatePath = $themePath . '/' . str_replace('/', DIRECTORY_SEPARATOR, $template);
				$raw = $this->files->exists($absoluteTemplatePath)
					? $this->files->get($absoluteTemplatePath)
					: '';
				$renderedLayoutPayload = $this->renderLayoutForEditor($themePath, $template, $label);

				return [
					'path' => $template,
					'label' => $label,
					'editor_html' => $renderedLayoutPayload['editor_html'] ?? $this->editorLayoutHtmlFromTwig($raw, $label),
					'html_attributes' => (array) ($renderedLayoutPayload['html_attributes'] ?? []),
					'body_attributes' => (array) ($renderedLayoutPayload['body_attributes'] ?? []),
					'preview_styles' => (array) ($renderedLayoutPayload['preview_styles'] ?? []),
					'preview_scripts' => (array) ($renderedLayoutPayload['preview_scripts'] ?? []),
				];
			})
			->sortBy('label')
			->values()
			->all();

		if ($layoutFiles === []) {
			return [
				[
					'path' => 'layouts/app.twig',
					'label' => 'App layout',
					'editor_html' => $this->defaultEditorLayoutHtml('app.twig'),
					'html_attributes' => [],
					'body_attributes' => [],
					'preview_styles' => [],
					'preview_scripts' => [],
				],
			];
		}

		return $layoutFiles;
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
	 * @return array{editor_html: string, html_attributes: array<string, string>, body_attributes: array<string, string>, preview_styles: array<int, array<string, mixed>>, preview_scripts: array<int, array<string, mixed>>}|null
	 */
	private function renderLayoutForEditor(string $themePath, string $template, string $label): ?array
	{
		$contentSlotHtml = '<main data-pbx-content-slot="true" class="pbx-layout-content"><section class="pbx-section"><h2 class="pbx-subheading">Editable content area</h2><p class="pbx-text">Drag, drop, add, remove, or edit blocks in this area.</p></section></main>';

		$rendered = $this->twigEngine->render([$themePath], $template, [
			'head' => '',
			'content' => $contentSlotHtml,
			'locale' => app()->getLocale(),
			'request_path' => '',
			'admin_prefix' => trim((string) config('app.admin_url_prefix', 'admin'), '/'),
		]);

		if (! is_string($rendered) || trim($rendered) === '') {
			return null;
		}

		$body = $this->extractBodyFromHtml($rendered);
		$markup = $body !== '' ? $body : $rendered;
		$htmlAttributes = $this->extractTagAttributesFromHtml($rendered, 'html');
		$bodyAttributes = $this->extractBodyAttributesFromHtml($rendered);
		$previewStyles = $this->extractPreviewSafeStyleLinksFromHtml($rendered);
		$previewScripts = $this->extractPreviewSafeScriptsFromHtml($rendered);

		if (! str_contains($markup, 'data-pbx-content-slot="true"')) {
			$markup .= $contentSlotHtml;
		}

		return [
			'editor_html' => '<div data-pbx-layout="' . e($label) . '">' . trim($markup) . '</div>',
			'html_attributes' => $htmlAttributes,
			'body_attributes' => $bodyAttributes,
			'preview_styles' => $previewStyles,
			'preview_scripts' => $previewScripts,
		];
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
