<?php

namespace Pagify\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Routing\Controller;
use Pagify\Core\Services\FrontendThemeManagerService;
use Pagify\Core\Services\FrontendThemeTwigEngine;
use Pagify\Core\Services\ModuleRegistry;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Services\PageSnapshotService;

class FrontendFallbackPageController extends Controller
{
    public function __invoke(
        Request $request,
        FrontendThemeManagerService $themes,
        FrontendThemeTwigEngine $twigEngine,
        ModuleRegistry $modules,
        PageSnapshotService $snapshotService,
    ): HttpResponse {
        $path = trim($request->path(), '/');

        if ($this->isReservedPath($path)) {
            abort(404);
        }

        $viewPaths = $themes->viewPathsForCurrentSite();
        $template = $this->resolveTemplate($path, $viewPaths);

        if ($template !== null) {
            $rendered = $twigEngine->render($viewPaths, $template, [
                'head' => '',
                'content' => '',
                'locale' => app()->getLocale(),
                'request_path' => $path,
                'admin_prefix' => trim((string) config('app.admin_url_prefix', 'admin'), '/'),
            ]);

            if (is_string($rendered) && $rendered !== '') {
                return response($rendered, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
            }
        }

        if (! $modules->enabled('page-builder')) {
            abort(404);
        }

        $slug = $path === '' ? 'home' : $path;

        $page = Page::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if ($page === null) {
            abort(404);
        }

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
        $content = $snapshotBody !== '' ? $snapshotBody : $html;

        $rendered = $twigEngine->render($viewPaths, 'pages/page.twig', [
            'page' => $page,
            'head' => $combinedHead,
            'content' => $content,
            'locale' => app()->getLocale(),
            'admin_prefix' => trim((string) config('app.admin_url_prefix', 'admin'), '/'),
        ]);

        if (is_string($rendered) && $rendered !== '') {
            return response($rendered, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * @param array<int, string> $viewPaths
     */
    private function resolveTemplate(string $path, array $viewPaths): ?string
    {
        $normalized = trim($path, '/');
        $candidates = [];

        if ($normalized === '') {
            $candidates = ['pages/home.twig', 'pages/index.twig'];
        } else {
            $candidates = [
                'pages/' . $normalized . '.twig',
                'pages/' . $normalized . '/index.twig',
            ];
        }

        foreach ($candidates as $candidate) {
            foreach ($viewPaths as $viewPath) {
                $absolute = rtrim($viewPath, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);

                if (is_file($absolute)) {
                    return $candidate;
                }
            }
        }

        return null;
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
}
