<?php

namespace Pagify\Core\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Pagify\Core\Models\Admin;
use Pagify\Media\Models\MediaAsset;

class ProfilePageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var Admin|null $admin */
        $admin = $request->user('web');

        $avatarUrl = null;

        if ($admin?->avatar_path !== null && $admin->avatar_path !== '') {
            $asset = MediaAsset::query()
                ->where('path', $admin->avatar_path)
                ->first();

            if ($asset !== null) {
                $avatarUrl = Storage::disk($asset->disk)->url($asset->path);
            }
        }

        return Inertia::render('Admin/Profile/Index', [
            'profile' => [
                'name' => $admin?->name,
                'username' => $admin?->username,
                'email' => $admin?->email,
                'nickname' => $admin?->nickname,
                'bio' => $admin?->bio,
                'avatar_url' => $avatarUrl,
            ],
            'apiRoutes' => [
                'updateProfile' => route('core.api.v1.admin.profile.update'),
                'updatePassword' => route('core.api.v1.admin.profile.password.update'),
                'uploadAvatar' => route('core.api.v1.admin.profile.avatar.store'),
                'removeAvatar' => route('core.api.v1.admin.profile.avatar.destroy'),
            ],
            'breadcrumbs' => [
                [
                    'href' => route('core.admin.dashboard'),
                    'label_key' => 'dashboard',
                ],
                [
                    'label_key' => 'profile',
                ],
            ],
        ]);
    }
}
