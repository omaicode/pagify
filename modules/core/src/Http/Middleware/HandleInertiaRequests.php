<?php

namespace Pagify\Core\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;
use Pagify\Core\Models\Admin;
use Pagify\Core\Services\ModuleRegistry;
use Pagify\Core\Support\SiteContext;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'core::admin.app';

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $translationCache = [];

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var ModuleRegistry $modules */
        $modules = app(ModuleRegistry::class);
        /** @var SiteContext $siteContext */
        $siteContext = app(SiteContext::class);
        $admin = $request->user('web');

        $menu = array_map(static function (array $item): array {
            $routeName = (string) ($item['route'] ?? '');
            $item['href'] = Route::has($routeName) ? route($routeName) : '#';

            return $item;
        }, $modules->adminMenu(static function (string $permission) use ($admin): bool {
            if ($admin === null) {
                return false;
            }

            if ($permission === 'core.token.manage') {
                return $admin->can('manageTokens', Admin::class);
            }

            return $admin->can($permission);
        }));

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'csrf_token' => csrf_token(),
            'locale' => app()->getLocale(),
            'supportedLocales' => config('core.locales.supported', ['en']),
            'localeUpdateUrl' => Route::has('core.admin.locale.update') ? route('core.admin.locale.update') : null,
            'settingsUrl' => Route::has('core.admin.settings.index') ? route('core.admin.settings.index') : null,
            'translations' => [
                'ui' => $this->adminUiTranslations(),
            ],
            'menu' => $menu,
            'currentSite' => $siteContext->site()?->only(['id', 'name', 'domain', 'locale']),
            'auth' => [
                'admin' => $admin?->only(['id', 'name', 'username', 'email', 'locale']),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function adminUiTranslations(): array
    {
        $locale = (string) app()->getLocale();
        $fallbackLocale = (string) config('app.fallback_locale', 'en');
        $cacheKey = sprintf('%s|%s', $locale, $fallbackLocale);

        if (isset($this->translationCache[$cacheKey])) {
            return $this->translationCache[$cacheKey];
        }

        $themesBasePath = trim((string) config('core.admin_ui.themes_base_path', 'themes/admin'), '/');
        $activeTheme = trim((string) config('core.admin_ui.theme', 'default'), '/');
        $fallbackTheme = trim((string) config('core.admin_ui.fallback_theme', 'default'), '/');

        $themes = array_values(array_unique(array_filter([
            $fallbackTheme,
            $activeTheme,
        ], static fn (string $theme): bool => $theme !== '')));

        $locales = array_values(array_unique(array_filter([
            $fallbackLocale,
            $locale,
        ], static fn (string $item): bool => $item !== '')));

        $translations = [];

        foreach ($themes as $theme) {
            foreach ($locales as $targetLocale) {
                $path = base_path(sprintf('%s/%s/lang/%s/ui.php', $themesBasePath, $theme, $targetLocale));
                $translations = array_replace_recursive($translations, $this->loadUiTranslationFile($path));
            }
        }

        $this->translationCache[$cacheKey] = $translations;

        return $translations;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadUiTranslationFile(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $data = require $path;

        return is_array($data) ? $data : [];
    }
}
