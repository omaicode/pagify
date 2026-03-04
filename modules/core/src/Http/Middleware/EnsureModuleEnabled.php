<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Services\ModuleRegistry;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        /** @var ModuleRegistry $modules */
        $modules = app(ModuleRegistry::class);

        if (! $modules->enabled($module)) {
            if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
                return $this->error();
            }

            abort(404);
        }

        return $next($request);
    }

    private function error(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 'MODULE_DISABLED',
            'message' => __('core::messages.api.module_disabled'),
            'errors' => [],
        ], 404);
    }
}
