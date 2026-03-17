<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Pagify\Core\Services\PluginManagerService;
use Pagify\Installer\Http\Middleware\EnsureInstallerCompleted;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(EnsureInstallerCompleted::class);
        $middleware->redirectGuestsTo(static function (Request $request): ?string {
            $adminPrefix = trim((string) config('app.admin_url_prefix', 'admin'), '/');

            if ($adminPrefix !== '' && (
                $request->is($adminPrefix, $adminPrefix.'/*')
                || $request->is('api/v1/'.$adminPrefix, 'api/v1/'.$adminPrefix.'/*')
            )) {
                return route('core.admin.login');
            }

            return null;
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $throwable, Request $request) {
            if (! app()->bound(PluginManagerService::class)) {
                return null;
            }

            /** @var PluginManagerService $plugins */
            $plugins = app(PluginManagerService::class);
            $disabledPlugin = $plugins->disablePluginFromThrowable($throwable);

            if ($disabledPlugin === null) {
                return null;
            }

            if ($request->expectsJson() || str_starts_with((string) $request->path(), 'api/')) {
                return response()->json([
                    'success' => false,
                    'code' => 'PLUGIN_SAFE_MODE_ENABLED',
                    'message' => sprintf('Plugin "%s" has been disabled by safe mode after an internal error.', $disabledPlugin),
                ], 503);
            }

            return response(
                sprintf('Plugin "%s" has been disabled by safe mode after an internal error.', $disabledPlugin),
                503
            );
        });
    })->create();
