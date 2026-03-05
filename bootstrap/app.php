<?php

use Illuminate\Http\Request;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Pagify\Core\Services\PluginManagerService;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(static fn () => route('core.admin.login'));
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
