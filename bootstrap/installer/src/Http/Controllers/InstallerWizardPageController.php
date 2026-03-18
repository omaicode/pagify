<?php

namespace Pagify\Installer\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;

class InstallerWizardPageController extends Controller
{
    public function __invoke(): View
    {
        $assets = $this->resolveAssets();
        $requestedLocale = request()->query('lang');
        if (is_string($requestedLocale) && in_array($requestedLocale, ['en', 'vi'], true)) {
            app()->setLocale($requestedLocale);
        }

        $locale = app()->getLocale();
        $translationBundles = [
            'en' => trans('installer::ui', locale: 'en'),
            'vi' => trans('installer::ui', locale: 'vi'),
        ];

        return view('installer::wizard', [
            'apiBase' => '/api/v1/install',
            'locale' => $locale,
            'translations' => $translationBundles[$locale] ?? $translationBundles['en'],
            'assets' => $assets,
            'payload' => [
                'apiBase' => '/api/v1/install',
                'locale' => $locale,
                'translations' => $translationBundles[$locale] ?? $translationBundles['en'],
                'translationBundles' => $translationBundles,
                'locales' => array_keys($translationBundles),
                'csrfToken' => csrf_token(),
            ],
        ]);
    }

    /**
     * @return array{css: array<int, string>, js: array<int, string>}
     */
    private function resolveAssets(): array
    {
        $manifestPath = public_path('build/installer/.vite/manifest.json');

        if (! is_file($manifestPath)) {
            return [
                'css' => [],
                'js' => [],
            ];
        }

        $decoded = json_decode((string) File::get($manifestPath), true);

        if (! is_array($decoded)) {
            return [
                'css' => [],
                'js' => [],
            ];
        }

        $entry = $decoded['src/main.js'] ?? null;

        if (! is_array($entry)) {
            return [
                'css' => [],
                'js' => [],
            ];
        }

        $jsFile = (string) ($entry['file'] ?? '');
        $cssFiles = array_values(array_filter(array_map('strval', (array) ($entry['css'] ?? [])), static fn (string $value): bool => $value !== ''));

        return [
            'css' => array_map(static fn (string $file): string => '/build/installer/'.$file, $cssFiles),
            'js' => $jsFile === '' ? [] : ['/build/installer/'.$jsFile],
        ];
    }
}
