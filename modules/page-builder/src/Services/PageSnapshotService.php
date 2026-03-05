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
		$layout = (array) ($page->layout_json ?? []);
		$grapes = (array) ($layout['grapes'] ?? []);
		$contentHtml = is_string($grapes['html'] ?? null) ? (string) $grapes['html'] : '';
		$contentCss = is_string($grapes['css'] ?? null) ? (string) $grapes['css'] : '';

		if (trim($contentHtml) === '') {
			$encodedLayout = json_encode($layout, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			$contentHtml = '<main><h1>' . $title . '</h1><pre>' . e(is_string($encodedLayout) ? $encodedLayout : '{}') . '</pre></main>';
		}

		$styles = trim($this->defaultBlockCss() . "\n" . $contentCss);
		$descriptionTag = $description !== '' ? "    <meta name=\"description\" content=\"{$description}\">" : '';
		$canonicalTag = $canonical !== '' ? "    <link rel=\"canonical\" href=\"{$canonical}\">" : '';
		$ogImageTag = $ogImage !== '' ? "    <meta property=\"og:image\" content=\"{$ogImage}\">" : '';
		$jsonLdTag = is_string($jsonLdString) ? '    <script type="application/ld+json">' . $jsonLdString . '</script>' : '';
		$styleTag = $styles !== '' ? "    <style>{$styles}</style>" : '';

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
{$styleTag}
</head>
<body>
{$contentHtml}
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

	private function defaultBlockCss(): string
	{
		return <<<'CSS'
.pbx-section{max-width:1120px;margin:0 auto;padding:clamp(16px,2vw,28px);font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#111827}
.pbx-heading{font-size:clamp(1.6rem,3vw,2.4rem);line-height:1.2;font-weight:700;margin:0 0 12px;color:#0f172a}
.pbx-subheading{font-size:clamp(1.05rem,1.8vw,1.3rem);line-height:1.35;font-weight:600;margin:0 0 8px;color:#0f172a}
.pbx-text{font-size:1rem;line-height:1.7;color:#475569;margin:0}
.pbx-caption{font-size:.875rem;line-height:1.5;color:#64748b;margin-top:8px}
.pbx-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:linear-gradient(135deg,#4b3fd8,#7c3aed);color:#fff;text-decoration:none;font-weight:600;font-size:.95rem;border-radius:999px;padding:10px 18px;box-shadow:0 10px 22px rgba(79,70,229,.25);transition:transform .2s ease,box-shadow .2s ease}
.pbx-btn:hover{transform:translateY(-1px);box-shadow:0 14px 28px rgba(79,70,229,.3)}
.pbx-btn--light{background:#fff;color:#4b3fd8;box-shadow:none}
.pbx-image{width:100%;height:auto;border-radius:16px;display:block;object-fit:cover;box-shadow:0 16px 40px rgba(15,23,42,.14)}
.pbx-columns{display:grid;gap:16px}
.pbx-columns-2{grid-template-columns:repeat(2,minmax(0,1fr))}
.pbx-columns-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.pbx-grid{display:grid;gap:16px;grid-template-columns:repeat(4,minmax(0,1fr))}
.pbx-card{padding:18px;border:1px solid #e5e7eb;border-radius:14px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.06)}
.pbx-video{position:relative;border-radius:16px;overflow:hidden;background:#000;padding-top:56.25%;box-shadow:0 14px 34px rgba(15,23,42,.2)}
.pbx-video iframe{position:absolute;inset:0;width:100%;height:100%;border:0}
.pbx-link-card{display:grid;gap:8px;padding:18px;border:1px solid #e5e7eb;border-radius:14px;background:#fff;text-decoration:none;color:inherit;transition:border-color .2s ease,transform .2s ease,box-shadow .2s ease}
.pbx-link-card:hover{border-color:#c4b5fd;transform:translateY(-1px);box-shadow:0 12px 24px rgba(91,33,182,.12)}
.pbx-link-card__eyebrow{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#7c3aed;font-weight:700}
.pbx-link-card__title{font-size:1.1rem;font-weight:700;color:#111827}
.pbx-link-card__desc{font-size:.95rem;line-height:1.65;color:#64748b}
.pbx-hero{display:grid;gap:16px;padding:clamp(28px,5vw,56px);border-radius:24px;background:radial-gradient(circle at top right,#dbeafe 0,#eef2ff 35%,#f8fafc 100%);border:1px solid #e2e8f0}
.pbx-eyebrow{margin:0 0 8px;font-size:.74rem;letter-spacing:.08em;text-transform:uppercase;color:#7c3aed;font-weight:700}
.pbx-hero__title{margin:0 0 10px;font-size:clamp(1.8rem,4vw,3rem);line-height:1.15;color:#0f172a}
.pbx-hero__text{margin:0 0 18px;font-size:1.05rem;line-height:1.75;color:#475569}
.pbx-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.pbx-stat{padding:18px;border-radius:14px;background:#0f172a;color:#f8fafc;display:grid;gap:4px}
.pbx-stat strong{font-size:1.5rem;line-height:1.2}
.pbx-stat span{font-size:.9rem;color:#cbd5e1}
.pbx-quote{margin:0;padding:22px;border-radius:16px;border:1px solid #e2e8f0;background:#fff;display:grid;gap:14px;color:#334155;font-size:1.05rem;line-height:1.8;box-shadow:0 10px 28px rgba(15,23,42,.08)}
.pbx-quote cite{font-style:normal;display:grid;gap:2px;color:#475569}
.pbx-quote cite span{font-weight:700;color:#0f172a}
.pbx-quote cite small{font-size:.85rem;color:#64748b}
.pbx-cta{padding:clamp(26px,4vw,44px);border-radius:18px;background:linear-gradient(135deg,#4b3fd8,#7c3aed);display:grid;gap:12px;color:#f8faff}
.pbx-cta__title{margin:0;font-size:clamp(1.4rem,3vw,2rem);line-height:1.2;color:#fff}
.pbx-cta__text{margin:0;color:#e9ddff;font-size:1rem;line-height:1.7}
.pbx-header{position:relative;background:#ffffff;border-bottom:1px solid #e5e7eb}
.pbx-header__inner{max-width:1120px;margin:0 auto;padding:14px clamp(16px,3vw,26px);display:flex;align-items:center;justify-content:space-between;gap:14px}
.pbx-header__brand{font-weight:700;color:#0f172a;text-decoration:none;font-size:1.05rem}
.pbx-header__nav{display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.pbx-header__nav a{color:#475569;text-decoration:none;font-size:.95rem;font-weight:500}
.pbx-footer{background:#0f172a;color:#e2e8f0;padding:clamp(22px,4vw,40px) clamp(16px,3vw,24px)}
.pbx-footer__grid{max-width:1120px;margin:0 auto;display:grid;gap:16px;grid-template-columns:2fr 1fr 1fr 1fr}
.pbx-footer h3,.pbx-footer h4{margin:0 0 10px;color:#fff}
.pbx-footer p{margin:0;color:#cbd5e1;line-height:1.6}
.pbx-footer a{display:block;color:#cbd5e1;text-decoration:none;margin-bottom:8px;font-size:.92rem}
.pbx-footer__meta{max-width:1120px;margin:16px auto 0;padding-top:14px;border-top:1px solid rgba(148,163,184,.25);font-size:.85rem;color:#94a3b8}
.pbx-pricing{display:grid;gap:16px;grid-template-columns:repeat(3,minmax(0,1fr))}
.pbx-pricing-card{position:relative;padding:20px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 10px 26px rgba(15,23,42,.08);display:grid;gap:12px}
.pbx-pricing-card h3{margin:0;color:#0f172a}
.pbx-pricing-card__price{margin:0;font-size:2rem;font-weight:700;color:#111827}
.pbx-pricing-card__price span{font-size:.95rem;font-weight:500;color:#64748b;margin-left:4px}
.pbx-pricing-card ul{margin:0;padding-left:18px;color:#475569;display:grid;gap:6px}
.pbx-pricing-card--featured{border-color:#a78bfa;box-shadow:0 16px 34px rgba(79,70,229,.2);transform:translateY(-3px)}
.pbx-pricing-card__badge{position:absolute;top:12px;right:12px;font-size:.72rem;font-weight:700;padding:4px 8px;border-radius:999px;background:#ede9fe;color:#5b21b6}
.pbx-faq{display:grid;gap:10px}
.pbx-faq details{border:1px solid #e2e8f0;border-radius:12px;background:#fff;padding:12px 14px}
.pbx-faq summary{cursor:pointer;font-weight:600;color:#0f172a}
.pbx-faq details p{margin:10px 0 0;color:#475569;line-height:1.7}
.pbx-contact{display:grid;gap:16px;grid-template-columns:1fr 1.2fr;align-items:start}
.pbx-contact__form{display:grid;gap:12px;padding:18px;border:1px solid #e5e7eb;border-radius:14px;background:#fff}
.pbx-contact__form label{display:grid;gap:6px;font-size:.9rem;color:#334155;font-weight:500}
.pbx-contact__form input,.pbx-contact__form textarea{width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:10px 12px;font-size:.95rem;line-height:1.5;outline:none}
.pbx-contact__form input:focus,.pbx-contact__form textarea:focus{border-color:#8b5cf6;box-shadow:0 0 0 3px rgba(139,92,246,.15)}
@media (max-width:1024px){.pbx-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:820px){.pbx-columns-2,.pbx-columns-3,.pbx-stats,.pbx-pricing,.pbx-contact,.pbx-footer__grid{grid-template-columns:1fr}.pbx-section{padding:16px}.pbx-grid{grid-template-columns:1fr}.pbx-header__inner{flex-wrap:wrap}.pbx-header__nav{width:100%;justify-content:flex-start}}
CSS;
	}
}
