<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Throwable;
use Illuminate\Http\Request;
use Modules\Core\Models\Site;
use Modules\Core\Support\SiteContext;
use Symfony\Component\HttpFoundation\Response;

class ResolveSite
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if ($host !== '') {
            try {
                $site = Site::query()
                    ->where('domain', $host)
                    ->where('is_active', true)
                    ->first();
            } catch (Throwable) {
                return $next($request);
            }

            if ($site !== null) {
                app(SiteContext::class)->setSite($site);
            }
        }

        return $next($request);
    }
}
