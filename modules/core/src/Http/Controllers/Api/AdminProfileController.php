<?php

namespace Pagify\Core\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Pagify\Core\Http\Requests\Admin\StoreAdminAvatarRequest;
use Pagify\Core\Http\Requests\Admin\UpdateAdminPasswordRequest;
use Pagify\Core\Http\Requests\Admin\UpdateAdminProfileRequest;
use Pagify\Core\Models\Admin;
use Pagify\Media\Services\MediaAssetManager;

class AdminProfileController extends ApiController
{
    public function update(UpdateAdminProfileRequest $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('web');

        $payload = $request->validated();

        $admin->forceFill([
            'name' => (string) $payload['name'],
            'nickname' => isset($payload['nickname']) && $payload['nickname'] !== '' ? (string) $payload['nickname'] : null,
            'bio' => isset($payload['bio']) && $payload['bio'] !== '' ? (string) $payload['bio'] : null,
        ])->save();

        return $this->success([
            'admin' => $this->profilePayload($admin->fresh()),
        ]);
    }

    public function updatePassword(UpdateAdminPasswordRequest $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('web');

        $payload = $request->validated();

        $admin->forceFill([
            'password' => Hash::make((string) $payload['new_password']),
        ])->save();

        return $this->success([
            'updated' => true,
        ]);
    }

    public function storeAvatar(StoreAdminAvatarRequest $request, MediaAssetManager $assetManager): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('web');

        $payload = $request->validated();
        $avatar = $payload['avatar'];

        $this->deleteCurrentAvatarAsset($admin);

        $asset = $assetManager->upload(
            file: $avatar,
            folder: null,
            siteId: $admin->site_id,
            uploadedByAdminId: $admin->id,
            maxImageWidth: 176,
        );

        $admin->forceFill([
            'avatar_path' => $asset->path,
        ])->save();

        return $this->success([
            'admin' => $this->profilePayload($admin->fresh()),
        ]);
    }

    public function destroyAvatar(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('web');

        $this->deleteCurrentAvatarAsset($admin);

        $admin->forceFill([
            'avatar_path' => null,
        ])->save();

        return $this->success([
            'admin' => $this->profilePayload($admin->fresh()),
        ]);
    }

    private function profilePayload(Admin $admin): array
    {
        $assetManager = app(MediaAssetManager::class);

        return [
            'name' => $admin->name,
            'username' => $admin->username,
            'email' => $admin->email,
            'nickname' => $admin->nickname,
            'bio' => $admin->bio,
            'avatar_url' => $assetManager->resolveUrlByPath($admin->avatar_path),
        ];
    }

    private function deleteCurrentAvatarAsset(Admin $admin): void
    {
        if ($admin->avatar_path === null || $admin->avatar_path === '') {
            return;
        }

        $assetManager = app(MediaAssetManager::class);
        $asset = $assetManager->findByPath($admin->avatar_path);

        if ($asset === null) {
            return;
        }

        $assetManager->delete($asset);
    }
}
