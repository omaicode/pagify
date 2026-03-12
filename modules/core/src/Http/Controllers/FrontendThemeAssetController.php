<?php

namespace Pagify\Core\Http\Controllers;

use Illuminate\Routing\Controller;
use Pagify\Core\Services\FrontendThemeManifestService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FrontendThemeAssetController extends Controller
{
    public function __invoke(string $theme, string $path, FrontendThemeManifestService $manifestService): BinaryFileResponse
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');

        if ($normalized === '' || str_contains($normalized, '..')) {
            abort(404);
        }

        $segments = explode('/', $normalized);
        $topDirectory = $segments[0] ?? '';

        if (! in_array($topDirectory, ['js', 'css', 'img'], true)) {
            abort(404);
        }

        $themesBasePath = trim((string) config('core.frontend_ui.themes_base_path', 'themes/main'), '/');
        $themePath = base_path($themesBasePath.'/'.$theme);

        if (! is_dir($themePath)) {
            abort(404);
        }

        $manifest = $manifestService->readManifestFile($themePath.'/theme.json');

        if (! is_array($manifest['manifest']) || (string) (($manifest['manifest']['slug'] ?? '')) !== $theme || $manifest['errors'] !== []) {
            abort(404);
        }

        $assetRoot = $themePath.'/assets';
        $assetRootReal = realpath($assetRoot);

        if ($assetRootReal === false || ! is_dir($assetRootReal)) {
            abort(404);
        }

        $assetPathReal = realpath($assetRootReal.'/'.$normalized);

        if ($assetPathReal === false || ! is_file($assetPathReal)) {
            abort(404);
        }

        $assetRootPrefix = rtrim($assetRootReal, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        if (! str_starts_with($assetPathReal, $assetRootPrefix)) {
            abort(404);
        }

        return response()->file($assetPathReal, [
            'Content-Type' => $this->contentTypeFor($assetPathReal),
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function contentTypeFor(string $assetPathReal): string
    {
        $extension = strtolower((string) pathinfo($assetPathReal, PATHINFO_EXTENSION));

        return match ($extension) {
            'css' => 'text/css; charset=utf-8',
            'js' => 'application/javascript; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            default => 'application/octet-stream',
        };
    }
}
