<?php

namespace Pagify\Installer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Pagify\Installer\Services\InstallerStateService;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstallerCompleted
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('installer.guard.enabled', true)) {
            return $next($request);
        }

        if (app()->environment('testing') && ! (bool) config('installer.guard.enable_in_testing', false)) {
            return $next($request);
        }

        $state = app(InstallerStateService::class);

        if ($state->isInstalled()) {
            if ($request->is('install') || $request->is('install/*') || $request->is('api/v1/install/*')) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'code' => 'INSTALLATION_ALREADY_COMPLETED',
                        'message' => 'The application has already been installed.',
                    ], 403);
                }

                return redirect('/');
            }

            return $next($request);
        }

        $this->forceSafeRuntimePersistenceDrivers();

        foreach ((array) config('installer.guard.except', []) as $pattern) {
            if (is_string($pattern) && $pattern !== '' && $request->is($pattern)) {
                return $next($request);
            }
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'code' => 'INSTALLATION_REQUIRED',
                'message' => 'Application setup is not complete. Please continue at /install.',
            ], 423);
        }

        return redirect('/install');
    }

    private function forceSafeRuntimePersistenceDrivers(): void
    {
        $sessionDriver = (string) config('session.driver', env('SESSION_DRIVER', 'database'));
        if ($sessionDriver === '' || $sessionDriver === 'database') {
            config(['session.driver' => 'file']);
        }

        $cacheStore = (string) config('cache.default', env('CACHE_STORE', 'database'));
        if ($cacheStore === '' || $cacheStore === 'database') {
            config(['cache.default' => 'file']);
        }
    }
}
