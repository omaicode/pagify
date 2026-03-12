<?php

namespace Pagify\Core\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Pagify\Core\Models\Site;
use Pagify\Core\Support\SiteContext;

class FrontendThemeManagerService
{
    public const ACTIVE_THEME_SETTING_KEY = 'theme.main.active';

    public function __construct(
        private readonly FrontendThemeManifestService $manifestService,
        private readonly SettingsManager $settingsManager,
        private readonly SiteContext $siteContext,
        private readonly Filesystem $files,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $basePath = $this->themesBasePath();

        if (! $this->files->isDirectory($basePath)) {
            return [];
        }

        $directories = array_values(array_filter(
            $this->files->directories($basePath),
            static fn (string $path): bool => basename($path) !== ''
        ));

        sort($directories);

        $usageIndex = $this->usageIndex();
        $defaultTheme = $this->defaultThemeSlug();
        $currentSiteId = $this->siteContext->siteId();
        $themes = [];

        foreach ($directories as $directory) {
            $slug = basename($directory);
            $manifestPath = $directory.'/theme.json';
            $parsed = $this->manifestService->readManifestFile($manifestPath);
            $manifest = $parsed['manifest'];
            $issues = $parsed['errors'];

            if (is_array($manifest) && (string) ($manifest['slug'] ?? '') !== $slug) {
                $issues[] = 'Theme slug in manifest does not match directory name.';
            }

            $activeSiteIds = $usageIndex[$slug] ?? [];

            $themes[] = [
                'slug' => $slug,
                'name' => (string) ($manifest['name'] ?? Str::headline($slug)),
                'version' => (string) ($manifest['version'] ?? '0.0.0'),
                'description' => (string) ($manifest['description'] ?? ''),
                'author' => (string) ($manifest['author'] ?? ''),
                'manifest' => $manifest,
                'is_valid' => $issues === [],
                'issues' => $issues,
                'is_default' => $slug === $defaultTheme,
                'usage_count' => count($activeSiteIds),
                'active_site_ids' => $activeSiteIds,
                'is_active_for_current_site' => $currentSiteId !== null && in_array($currentSiteId, $activeSiteIds, true),
                'can_delete' => $slug !== $defaultTheme && $activeSiteIds === [],
            ];
        }

        return $themes;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(array $payload): array
    {
        $slug = (string) ($payload['slug'] ?? '');
        $themePath = $this->themePath($slug);

        if ($slug === '') {
            return ['ok' => false, 'code' => 'THEME_INVALID', 'message' => 'Theme slug is required.', 'status' => 422];
        }

        if ($this->files->exists($themePath)) {
            return ['ok' => false, 'code' => 'THEME_ALREADY_EXISTS', 'message' => 'Theme already exists.', 'status' => 422];
        }

        $this->files->ensureDirectoryExists($this->themesBasePath());
        $this->files->ensureDirectoryExists($themePath.'/resources/views/layouts');
        $this->files->ensureDirectoryExists($themePath.'/resources/views/pages');
        $this->files->ensureDirectoryExists($themePath.'/lang/en');
        $this->files->ensureDirectoryExists($themePath.'/lang/vi');

        $manifest = [
            'slug' => $slug,
            'name' => (string) ($payload['name'] ?? Str::headline($slug)),
            'version' => (string) ($payload['version'] ?? '1.0.0'),
            'description' => (string) ($payload['description'] ?? ''),
            'author' => (string) ($payload['author'] ?? ''),
            'requires' => [
                'php' => '>=8.2',
                'laravel' => '^12.0',
            ],
            'supports' => [
                'pagify' => '^1.0',
            ],
        ];

        $this->writeManifest($themePath.'/theme.json', $manifest);
        $this->files->put($themePath.'/README.md', $this->defaultReadme($manifest));
        $this->files->put($themePath.'/resources/views/layouts/app.blade.php', $this->defaultLayoutTemplate($manifest));
        $this->files->put($themePath.'/resources/views/pages/page.blade.php', $this->defaultPageTemplate());
        $this->files->put($themePath.'/lang/en/theme.php', "<?php\n\nreturn [];\n");
        $this->files->put($themePath.'/lang/vi/theme.php', "<?php\n\nreturn [];\n");

        return [
            'ok' => true,
            'theme' => $this->findBySlug($slug),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(string $slug, array $payload): array
    {
        $themePath = $this->themePath($slug);

        if (! $this->files->isDirectory($themePath)) {
            return ['ok' => false, 'code' => 'THEME_NOT_FOUND', 'message' => 'Theme not found.', 'status' => 404];
        }

        $manifestPath = $themePath.'/theme.json';
        $parsed = $this->manifestService->readManifestFile($manifestPath);

        $manifest = is_array($parsed['manifest']) ? $parsed['manifest'] : ['slug' => $slug];

        $manifest['slug'] = $slug;

        foreach (['name', 'version', 'description', 'author'] as $field) {
            if (array_key_exists($field, $payload)) {
                $manifest[$field] = (string) $payload[$field];
            }
        }

        if (! isset($manifest['name']) || trim((string) $manifest['name']) === '') {
            $manifest['name'] = Str::headline($slug);
        }

        if (! isset($manifest['version']) || trim((string) $manifest['version']) === '') {
            $manifest['version'] = '1.0.0';
        }

        $this->writeManifest($manifestPath, $manifest);

        return [
            'ok' => true,
            'theme' => $this->findBySlug($slug),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function activate(string $slug, ?int $siteId = null): array
    {
        $theme = $this->findBySlug($slug);

        if ($theme === null) {
            return ['ok' => false, 'code' => 'THEME_NOT_FOUND', 'message' => 'Theme not found.', 'status' => 404];
        }

        if (($theme['is_valid'] ?? false) !== true) {
            return ['ok' => false, 'code' => 'THEME_INVALID', 'message' => 'Theme manifest is invalid.', 'status' => 422, 'errors' => ['issues' => (array) ($theme['issues'] ?? [])]];
        }

        $targetSiteId = $siteId ?? $this->siteContext->siteId();

        if ($targetSiteId === null) {
            return ['ok' => false, 'code' => 'THEME_SITE_REQUIRED', 'message' => 'Site id is required.', 'status' => 422];
        }

        $site = Site::query()->find($targetSiteId);

        if ($site === null) {
            return ['ok' => false, 'code' => 'THEME_SITE_NOT_FOUND', 'message' => 'Target site not found.', 'status' => 404];
        }

        $this->settingsManager->set(self::ACTIVE_THEME_SETTING_KEY, $slug, (int) $site->id);

        return [
            'ok' => true,
            'theme' => $this->findBySlug($slug),
            'site_id' => (int) $site->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $slug): array
    {
        $themePath = $this->themePath($slug);

        if (! $this->files->isDirectory($themePath)) {
            return ['ok' => false, 'code' => 'THEME_NOT_FOUND', 'message' => 'Theme not found.', 'status' => 404];
        }

        if ($slug === $this->defaultThemeSlug()) {
            return ['ok' => false, 'code' => 'THEME_LOCKED', 'message' => 'Default theme is locked.', 'status' => 422];
        }

        $usageSites = $this->usageIndex()[$slug] ?? [];

        if ($usageSites !== []) {
            return [
                'ok' => false,
                'code' => 'THEME_IN_USE',
                'message' => 'Theme is currently active on one or more sites.',
                'status' => 409,
                'errors' => [
                    'usage_count' => [count($usageSites)],
                    'site_ids' => $usageSites,
                ],
            ];
        }

        $this->files->deleteDirectory($themePath);

        return ['ok' => true];
    }

    public function activeThemeForSite(int $siteId): string
    {
        $active = $this->settingsManager->get(self::ACTIVE_THEME_SETTING_KEY, $this->defaultThemeSlug(), $siteId);

        return is_string($active) && trim($active) !== ''
            ? trim($active)
            : $this->defaultThemeSlug();
    }

    public function activeThemeForCurrentSite(): string
    {
        $siteId = $this->siteContext->siteId();

        if ($siteId === null) {
            return $this->defaultThemeSlug();
        }

        return $this->activeThemeForSite($siteId);
    }

    /**
     * @return array<int, string>
     */
    public function viewPathsForCurrentSite(): array
    {
        $active = $this->activeThemeForCurrentSite();
        $fallback = trim((string) config('core.frontend_ui.fallback_theme', 'default'));

        $slugs = array_values(array_unique(array_filter([
            $active,
            $fallback,
            $this->defaultThemeSlug(),
        ], static fn (string $slug): bool => $slug !== '')));

        $paths = [];

        foreach ($slugs as $slug) {
            $path = $this->themePath($slug).'/resources/views';

            if ($this->files->isDirectory($path)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    private function themesBasePath(): string
    {
        $basePath = trim((string) config('core.frontend_ui.themes_base_path', 'themes/main'), '/');

        return base_path($basePath);
    }

    private function defaultThemeSlug(): string
    {
        $default = trim((string) config('core.frontend_ui.theme', 'default'));

        return $default === '' ? 'default' : $default;
    }

    /**
     * @return array<string, array<int, int>>
     */
    private function usageIndex(): array
    {
        $usage = [];

        $siteIds = Site::query()->pluck('id')->all();

        foreach ($siteIds as $siteId) {
            $activeTheme = $this->activeThemeForSite((int) $siteId);
            $usage[$activeTheme] ??= [];
            $usage[$activeTheme][] = (int) $siteId;
        }

        return $usage;
    }

    private function themePath(string $slug): string
    {
        return $this->themesBasePath().'/'.$slug;
    }

    private function writeManifest(string $manifestPath, array $manifest): void
    {
        $encoded = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->files->put($manifestPath, (is_string($encoded) ? $encoded : '{}')."\n");
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findBySlug(string $slug): ?array
    {
        foreach ($this->all() as $theme) {
            if ((string) ($theme['slug'] ?? '') === $slug) {
                return $theme;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function defaultReadme(array $manifest): string
    {
        $name = (string) ($manifest['name'] ?? 'Theme');
        $slug = (string) ($manifest['slug'] ?? 'theme');

        return "# {$name}\n\nTheme slug: {$slug}\n\nThis frontend theme was generated by Pagify Theme Manager.\n";
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function defaultLayoutTemplate(array $manifest): string
    {
        $name = e((string) ($manifest['name'] ?? 'Theme'));

        return "<!doctype html>\n<html lang=\"{{ str_replace('_', '-', app()->getLocale()) }}\">\n<head>\n    <meta charset=\"utf-8\">\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n    <title>@yield('title', '{$name}')</title>\n    @yield('head')\n</head>\n<body>\n    @yield('content')\n</body>\n</html>\n";
    }

    private function defaultPageTemplate(): string
    {
        return "@extends('layouts.app')\n\n@section('head')\n    {!! \$head ?? '' !!}\n@endsection\n\n@section('content')\n    {!! \$content ?? '' !!}\n@endsection\n";
    }
}
