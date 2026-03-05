<?php

namespace Pagify\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Pagify\Core\Services\AuditLogger;
use Throwable;

class RecordAuditLog
{
    private const TRACKED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * @param Closure(Request): \Symfony\Component\HttpFoundation\Response $next
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! in_array($request->method(), self::TRACKED_METHODS, true)) {
            return $response;
        }

        $admin = $request->user('web');

        if ($admin === null) {
            return $response;
        }

        if ($response->getStatusCode() >= 500) {
            return $response;
        }

        $routeName = (string) ($request->route()?->getName() ?? 'unknown');
        $action = 'http.' . strtolower($request->method());

        if ($routeName !== 'unknown') {
            $action = 'route.' . $routeName;
        }

        $metadata = [
            'method' => $request->method(),
            'path' => $request->path(),
            'route_name' => $routeName,
            'status_code' => $response->getStatusCode(),
            'request' => $request->except(['password', 'password_confirmation', 'token']),
        ];

        try {
            app(AuditLogger::class)->log(
                action: $action,
                entityType: 'route',
                entityId: $routeName,
                metadata: $metadata,
            );
        } catch (Throwable) {
            return $response;
        }

        return $response;
    }
}
