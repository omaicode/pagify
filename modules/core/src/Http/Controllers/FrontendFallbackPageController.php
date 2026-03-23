<?php

namespace Pagify\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Routing\Controller;
use Pagify\Core\Services\FrontendThemeManagerService;
use Pagify\Core\Services\ModuleRegistry;
use Pagify\Core\Wsre\WsreEngine;
use Pagify\PageBuilder\Models\Page;

class FrontendFallbackPageController extends Controller
{
    public function __invoke(
        Request $request,
        FrontendThemeManagerService $themes,
        WsreEngine $wsreEngine,
        ModuleRegistry $modules,
    ): HttpResponse {
        $path = trim($request->path(), '/');

        if ($this->isReservedPath($path)) {
            abort(404);
        }

        $viewPaths = $themes->viewPathsForCurrentSite();
        $wsreRendered = $wsreEngine->render($viewPaths, $path, [
            'locale' => app()->getLocale(),
            'request_path' => $path,
            'admin_prefix' => trim((string) config('app.admin_url_prefix', 'admin'), '/'),
        ]);

        if (is_string($wsreRendered) && $wsreRendered !== '') {
            return response($wsreRendered, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        if (! $modules->enabled('page-builder')) {
            abort(404);
        }

        $slug = $path === '' ? 'home' : $path;

        $page = $path === ''
            ? Page::query()
                ->where('is_home', true)
                ->orWhereIn('slug', ['/', 'home'])
                ->orderByRaw("CASE WHEN is_home = 1 THEN 0 ELSE 1 END")
                ->orderByDesc('id')
                ->first()
            : Page::query()->where('slug', $slug)->first();

        if ($page === null) {
            abort(404);
        }

        $layout = (array) ($page->layout_json ?? []);
        $editorMarkup = $this->resolveEditorMarkupFromLayout($layout);
        $editorHtml = $editorMarkup['html'];
        $editorCss = $editorMarkup['css'];
        $slotContent = $this->extractContentSlotHtml($editorHtml);
        $content = $slotContent !== '' ? $slotContent : trim($editorHtml);

        if ($content === '') {
            $content = '<section class="pbx-section"><h2 class="pbx-subheading">Content is empty</h2></section>';
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
            $editorCss !== '' ? '<style>' . $editorCss . '</style>' : '',
            is_string($jsonLdString) ? '<script type="application/ld+json">' . $jsonLdString . '</script>' : '',
        ];

        $head = implode("\n", array_filter($headParts));


		$document = '<!doctype html><html><head>' . $head . '</head><body>' . $content . '</body></html>';

		return response($document, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function isReservedPath(string $path): bool
    {
        $adminPrefix = trim((string) config('app.admin_url_prefix'), '/');

        if ($path === 'theme-assets' || str_starts_with($path, 'theme-assets/')) {
            return true;
        }

        if ($path === 'api' || str_starts_with($path, 'api/')) {
            return true;
        }

        if ($adminPrefix !== '' && ($path === $adminPrefix || str_starts_with($path, $adminPrefix . '/'))) {
            return true;
        }

        return false;
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
}
