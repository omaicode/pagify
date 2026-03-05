<?php

namespace Pagify\Media\Policies;

use Pagify\Core\Models\Admin;
use Pagify\Media\Models\MediaAsset;

class MediaAssetPolicy
{
    public function viewAny(Admin $admin): bool
    {
        return $admin->can('media.asset.viewAny');
    }

    public function view(Admin $admin, MediaAsset $asset): bool
    {
        return $admin->can('media.asset.view') || $admin->can('media.asset.viewAny');
    }

    public function create(Admin $admin): bool
    {
        return $admin->can('media.asset.create');
    }

    public function update(Admin $admin, MediaAsset $asset): bool
    {
        return $admin->can('media.asset.update');
    }

    public function delete(Admin $admin, MediaAsset $asset): bool
    {
        return $admin->can('media.asset.delete');
    }
}
