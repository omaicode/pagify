<?php

namespace Modules\Core\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;
use Modules\Core\Services\ModuleRegistry;
use Modules\Core\Support\SiteContext;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'core::admin.app';

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var ModuleRegistry $modules */
        $modules = app(ModuleRegistry::class);
        /** @var SiteContext $siteContext */
        $siteContext = app(SiteContext::class);

        $menu = array_map(static function (array $item): array {
            $routeName = (string) ($item['route'] ?? '');
            $item['href'] = Route::has($routeName) ? route($routeName) : '#';

            return $item;
        }, $modules->adminMenu());

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'locale' => app()->getLocale(),
            'supportedLocales' => config('core.locales.supported', ['en']),
            'menu' => $menu,
            'currentSite' => $siteContext->site()?->only(['id', 'name', 'domain', 'locale']),
            'auth' => [
                'admin' => $request->user('web')?->only(['id', 'name', 'username', 'email', 'locale']),
            ],
        ];
    }
}
