<?php

namespace Pagify\Core\Policies;

use Pagify\Core\Models\Admin;
use Pagify\Core\Models\Setting;

class SettingPolicy
{
    public function viewAny(Admin $admin): bool
    {
        return $admin->can('core.setting.viewAny');
    }

    public function view(Admin $admin, Setting $setting): bool
    {
        return $admin->can('core.setting.view') || $admin->site_id === $setting->site_id;
    }

    public function create(Admin $admin): bool
    {
        return $admin->can('core.setting.create');
    }

    public function update(Admin $admin, Setting $setting): bool
    {
        return $admin->can('core.setting.update') || $admin->site_id === $setting->site_id;
    }
}
