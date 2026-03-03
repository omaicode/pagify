<?php

namespace Modules\Core\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;
use Modules\Core\Models\Admin;
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
            'locale' => app()->getLocale(),
            'supportedLocales' => config('core.locales.supported', ['en']),
            'localeUpdateUrl' => Route::has('core.admin.locale.update') ? route('core.admin.locale.update') : null,
            'translations' => [
                'ui' => trans('core::ui'),
            ],
            'menu' => $menu,
            'currentSite' => $siteContext->site()?->only(['id', 'name', 'domain', 'locale']),
            'auth' => [
                'admin' => $admin?->only(['id', 'name', 'username', 'email', 'locale']),
            ],
        ];
    }
}
