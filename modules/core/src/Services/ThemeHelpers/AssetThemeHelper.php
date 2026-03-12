<?php

namespace Pagify\Core\Services\ThemeHelpers;

use Pagify\Core\Services\FrontendThemeManagerService;

class AssetThemeHelper
{
    public function __construct(
        private readonly FrontendThemeManagerService $themes,
    ) {
    }

    public function url(string $path = '', ?string $theme = null): string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');

        if ($normalized === '') {
            return url('/');
        }

        $themeSlug = $theme !== null && trim($theme) !== ''
            ? trim($theme)
            : $this->themes->activeThemeForCurrentSite();

        $url = route('core.frontend.themes.assets', [
            'theme' => $themeSlug,
            'path' => $normalized,
        ]);

        $version = $this->assetVersion($themeSlug, $normalized);

        if ($version === null) {
            return $url;
        }

        return $url.'?v='.$version;
    }

    private function assetVersion(string $themeSlug, string $normalizedPath): ?string
    {
        if (str_contains($normalizedPath, '..')) {
            return null;
        }

        $strategy = strtolower((string) config('core.frontend_ui.assets.cache_busting.strategy', 'mtime'));

        if ($strategy === 'off') {
            return null;
        }

        $themesBasePath = trim((string) config('core.frontend_ui.themes_base_path', 'themes/main'), '/');
        $assetAbsolutePath = base_path($themesBasePath.'/'.$themeSlug.'/assets/'.$normalizedPath);

        if (! is_file($assetAbsolutePath)) {
            return null;
        }

        if ($strategy === 'hash') {
            $hash = @sha1_file($assetAbsolutePath);

            if (! is_string($hash) || $hash === '') {
                return null;
            }

            $length = (int) config('core.frontend_ui.assets.cache_busting.hash_length', 12);
            $length = max(6, min(40, $length));

            return substr($hash, 0, $length);
        }

        $mtime = @filemtime($assetAbsolutePath);

        return is_int($mtime) ? (string) $mtime : null;
    }
}
