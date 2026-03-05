<?php

namespace Pagify\Core\Policies;

use Pagify\Core\Models\Admin;

class AdminPolicy
{
    public function managePermissions(Admin $admin): bool
    {
        return $admin->can('core.permission.manage');
    }

    public function manageAdmins(Admin $admin): bool
    {
        return $admin->can('core.admin.manage');
    }

    public function manageAdminGroups(Admin $admin): bool
    {
        return $admin->can('core.admin.group.manage');
    }

    public function manageTokens(Admin $admin): bool
    {
        return $admin->can('core.token.manage');
    }

    public function manageModules(Admin $admin): bool
    {
        return $admin->can('core.module.manage');
    }

    public function manageUpdater(Admin $admin): bool
    {
        return $admin->can('core.updater.manage');
    }
}
