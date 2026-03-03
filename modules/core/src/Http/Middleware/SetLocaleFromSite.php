<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Modules\Core\Support\SiteContext;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromSite
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $site = app(SiteContext::class)->site();
        $supportedLocales = config('core.locales.supported', ['en']);
        $defaultLocale = config('core.locales.default', 'en');

        $locale = $site?->locale;

        if (! is_string($locale) || ! in_array($locale, $supportedLocales, true)) {
            $locale = $defaultLocale;
        }

        App::setLocale($locale);

        View::share('locale', $locale);
        View::share('supportedLocales', $supportedLocales);

        return $next($request);
    }
}
