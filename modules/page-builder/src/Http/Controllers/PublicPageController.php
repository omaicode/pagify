<?php

namespace Pagify\PageBuilder\Http\Controllers;

use Illuminate\Http\Response as HttpResponse;
use Illuminate\Routing\Controller;
use Pagify\Core\Services\FrontendThemeTwigEngine;
use Pagify\Core\Services\FrontendThemeManagerService;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Services\PageSnapshotService;

class PublicPageController extends Controller
{
	public function __invoke(
		string $slug,
		PageSnapshotService $snapshotService,
		FrontendThemeManagerService $themes,
		FrontendThemeTwigEngine $twigEngine,
	): HttpResponse
	{
		$page = Page::query()
			->where('slug', $slug)
			->where('status', 'published')
			->firstOrFail();

		if (! is_string($page->snapshot_html) || trim($page->snapshot_html) === '') {
			$snapshotService->refresh($page);
			$page->refresh();
		}

		$html = (string) ($page->snapshot_html ?? '');
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
		];

		$head = implode("\n", array_filter($headParts));

		$snapshotHead = $this->extractHeadContent($html);
		$snapshotBody = $this->extractBodyContent($html);
		$combinedHead = trim(implode("\n", array_filter([$snapshotHead, $head])));
		$slotContent = $this->extractContentSlotHtml($snapshotBody !== '' ? $snapshotBody : $html);
		$content = $slotContent !== '' ? $slotContent : ($snapshotBody !== '' ? $snapshotBody : $html);

		$rendered = $twigEngine->render($themes->viewPathsForCurrentSite(), 'pages/home.twig', [
			'page' => $page,
			'head' => $combinedHead,
			'content' => $content,
			'locale' => app()->getLocale(),
			'request_path' => $slug,
			'admin_prefix' => trim((string) config('app.admin_url_prefix', 'admin'), '/'),
		]);

		if (is_string($rendered) && $rendered !== '') {
			return response($rendered, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
		}

		return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
	}

	private function extractHeadContent(string $html): string
	{
		if (preg_match('/<head[^>]*>(.*?)<\/head>/is', $html, $matches) !== 1) {
			return '';
		}

		return trim((string) ($matches[1] ?? ''));
	}

	private function extractBodyContent(string $html): string
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
}
