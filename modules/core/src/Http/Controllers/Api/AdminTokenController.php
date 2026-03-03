<?php

namespace Modules\Core\Http\Controllers\Api;

use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Modules\Core\Models\Admin;

class AdminTokenController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('web');

        $tokens = $admin->tokens()
            ->latest('id')
            ->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at'])
            ->map(static fn (PersonalAccessToken $token): array => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => optional($token->last_used_at)?->toDateTimeString(),
                'expires_at' => optional($token->expires_at)?->toDateTimeString(),
                'created_at' => optional($token->created_at)?->toDateTimeString(),
            ])
            ->values();

        return $this->success($tokens);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string', 'max:80'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        /** @var Admin $admin */
        $admin = $request->user('web');

        $token = $admin->createToken(
            name: $payload['name'],
            abilities: $payload['abilities'] ?? ['*'],
            expiresAt: isset($payload['expires_at']) ? Carbon::parse((string) $payload['expires_at']) : null,
        );

        return $this->success([
            'id' => $token->accessToken->id,
            'name' => $token->accessToken->name,
            'token' => $token->plainTextToken,
            'abilities' => $token->accessToken->abilities,
            'expires_at' => optional($token->accessToken->expires_at)?->toDateTimeString(),
        ], 201);
    }

    public function destroy(Request $request, int $tokenId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('web');

        $deleted = $admin->tokens()->whereKey($tokenId)->delete();

        if ($deleted === 0) {
            return $this->error('Token not found.', 404);
        }

        return $this->success([
            'deleted' => true,
        ]);
    }
}
