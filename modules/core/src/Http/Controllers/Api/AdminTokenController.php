<?php

namespace Modules\Core\Http\Controllers\Api;

use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Core\Http\Requests\Admin\StoreAdminTokenRequest;
use Modules\Core\Http\Resources\AdminTokenCreatedResource;
use Modules\Core\Http\Resources\AdminTokenResource;
use Modules\Core\Models\Admin;

class AdminTokenController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('manageTokens', Admin::class);

        /** @var Admin $admin */
        $admin = $request->user('web');

        $tokens = $admin->tokens()
            ->latest('id')
            ->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at']);

        return $this->success(AdminTokenResource::collection($tokens)->resolve());
    }

    public function store(StoreAdminTokenRequest $request): JsonResponse
    {
        $this->authorize('manageTokens', Admin::class);

        $payload = $request->validated();

        /** @var Admin $admin */
        $admin = $request->user('web');

        $token = $admin->createToken(
            name: $payload['name'],
            abilities: $payload['abilities'] ?? ['*'],
            expiresAt: isset($payload['expires_at']) ? Carbon::parse((string) $payload['expires_at']) : null,
        );

        $resource = new AdminTokenCreatedResource([
            'id' => $token->accessToken->id,
            'name' => $token->accessToken->name,
            'token' => $token->plainTextToken,
            'abilities' => $token->accessToken->abilities,
            'expires_at' => optional($token->accessToken->expires_at)?->toDateTimeString(),
        ]);

        return $this->success($resource->resolve(), 201);
    }

    public function destroy(Request $request, int $tokenId): JsonResponse
    {
        $this->authorize('manageTokens', Admin::class);

        /** @var Admin $admin */
        $admin = $request->user('web');

        $deleted = $admin->tokens()->whereKey($tokenId)->delete();

        if ($deleted === 0) {
            return $this->error('Token not found.', 404, 'TOKEN_NOT_FOUND');
        }

        return $this->success([
            'deleted' => true,
        ]);
    }
}
