<?php

namespace Pagify\PageBuilder\Services;

use Pagify\PageBuilder\Models\Page;

class PageSnapshotService
{
	public function generate(Page $page): string
	{
		$seo = (array) ($page->seo_meta_json ?? []);
		$title = e((string) ($seo['title'] ?? $page->title));
		$description = e((string) ($seo['description'] ?? ''));
		$canonical = e((string) ($seo['canonical_url'] ?? ''));
		$ogImage = e((string) ($seo['og_image'] ?? ''));
		$jsonLd = $seo['json_ld'] ?? null;
		$jsonLdString = is_array($jsonLd) ? json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
		$layout = json_encode((array) ($page->layout_json ?? []), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		$layout = is_string($layout) ? e($layout) : '{}';
		$descriptionTag = $description !== '' ? "    <meta name=\"description\" content=\"{$description}\">" : '';
		$canonicalTag = $canonical !== '' ? "    <link rel=\"canonical\" href=\"{$canonical}\">" : '';
		$ogImageTag = $ogImage !== '' ? "    <meta property=\"og:image\" content=\"{$ogImage}\">" : '';
		$jsonLdTag = is_string($jsonLdString) ? '    <script type="application/ld+json">' . $jsonLdString . '</script>' : '';

		return <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title}</title>
{$descriptionTag}
{$canonicalTag}
{$ogImageTag}
{$jsonLdTag}
</head>
<body>
    <main>
        <h1>{$title}</h1>
        <pre>{$layout}</pre>
    </main>
</body>
</html>
HTML;
	}

	public function refresh(Page $page): Page
	{
		$page->forceFill([
			'snapshot_html' => $this->generate($page),
			'snapshot_generated_at' => now(),
		])->save();

		return $page->refresh();
	}
}
