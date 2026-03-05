<?php

namespace Pagify\Core\Policies;

use Pagify\Core\Models\Admin;

class AdminPolicy
{
    public function manageTokens(Admin $admin): bool
    {
        return $admin->can('core.token.manage');
    }

    public function manageModules(Admin $admin): bool
    {
        return $admin->can('core.module.manage');
    }
}
