<?php

namespace Pagify\Installer\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class InstallerApiController extends Controller
{
    use AuthorizesRequests;

    protected function success(mixed $data = null, int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => $meta,
        ], $status);
    }

    protected function error(string $message, int $status = 400, string $code = 'BAD_REQUEST', array $errors = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $code,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
