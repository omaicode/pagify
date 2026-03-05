<?php

namespace Pagify\Core\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Pagify\Core\Http\Requests\Admin\StoreAdminAvatarRequest;
use Pagify\Core\Http\Requests\Admin\UpdateAdminPasswordRequest;
use Pagify\Core\Http\Requests\Admin\UpdateAdminProfileRequest;
use Pagify\Core\Models\Admin;
use Pagify\Media\Models\MediaAsset;
use Pagify\Media\Services\MediaStorageManager;

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

    public function storeAvatar(StoreAdminAvatarRequest $request, MediaStorageManager $storageManager): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('web');

        $payload = $request->validated();
        $avatar = $payload['avatar'];

        $this->deleteCurrentAvatarAsset($admin);

        $stored = $storageManager->storeUploadedFile($avatar, 'admin-avatars');

        MediaAsset::query()->create([
            'site_id' => $admin->site_id,
            'uuid' => (string) Str::uuid(),
            'disk' => $stored['disk'],
            'path' => $stored['path'],
            'path_hash' => hash('sha256', $stored['path']),
            'filename' => $stored['filename'],
            'original_name' => (string) $avatar->getClientOriginalName(),
            'mime_type' => (string) $avatar->getClientMimeType(),
            'extension' => strtolower((string) $avatar->getClientOriginalExtension()),
            'size_bytes' => (int) $avatar->getSize(),
            'kind' => 'image',
            'uploaded_by_admin_id' => $admin->id,
            'uploaded_at' => now(),
        ]);

        $admin->forceFill([
            'avatar_path' => $stored['path'],
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
        $avatarUrl = null;

        if ($admin->avatar_path !== null && $admin->avatar_path !== '') {
            $asset = MediaAsset::query()
                ->where('path', $admin->avatar_path)
                ->first();

            if ($asset !== null) {
                $avatarUrl = Storage::disk($asset->disk)->url($asset->path);
            }
        }

        return [
            'name' => $admin->name,
            'username' => $admin->username,
            'email' => $admin->email,
            'nickname' => $admin->nickname,
            'bio' => $admin->bio,
            'avatar_url' => $avatarUrl,
        ];
    }

    private function deleteCurrentAvatarAsset(Admin $admin): void
    {
        if ($admin->avatar_path === null || $admin->avatar_path === '') {
            return;
        }

        $asset = MediaAsset::query()
            ->where('path', $admin->avatar_path)
            ->first();

        if ($asset === null) {
            return;
        }

        Storage::disk($asset->disk)->delete($asset->path);
        $asset->delete();
    }
}
