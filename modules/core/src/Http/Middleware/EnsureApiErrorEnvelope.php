<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class EnsureApiErrorEnvelope
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        try {
            $response = $next($request);

            if ($response->getStatusCode() === 302 && str_contains((string) $response->headers->get('Location'), '/admin/login')) {
                return $this->error(
                    message: __('core::messages.api.authentication_required'),
                    code: 'AUTHENTICATION_REQUIRED',
                    status: 401,
                );
            }

            return $response;
        } catch (ValidationException $exception) {
            return $this->error(
                message: __('core::messages.api.validation_failed'),
                code: 'VALIDATION_FAILED',
                status: 422,
                errors: $exception->errors(),
            );
        } catch (AuthenticationException $exception) {
            return $this->error(
                message: __('core::messages.api.authentication_required'),
                code: 'AUTHENTICATION_REQUIRED',
                status: 401,
            );
        } catch (AuthorizationException $exception) {
            return $this->error(
                message: __('core::messages.api.forbidden'),
                code: 'FORBIDDEN',
                status: 403,
            );
        } catch (ModelNotFoundException $exception) {
            return $this->error(
                message: __('core::messages.api.resource_not_found'),
                code: 'RESOURCE_NOT_FOUND',
                status: 404,
            );
        } catch (HttpExceptionInterface $exception) {
            return $this->error(
                message: $exception->getMessage() !== '' ? $exception->getMessage() : __('core::messages.api.http_error'),
                code: 'HTTP_ERROR',
                status: $exception->getStatusCode(),
            );
        } catch (Throwable $exception) {
            report($exception);

            return $this->error(
                message: __('core::messages.api.internal_server_error'),
                code: 'INTERNAL_SERVER_ERROR',
                status: 500,
            );
        }
    }

    /**
     * @param array<string, mixed> $errors
     */
    private function error(string $message, string $code, int $status, array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
