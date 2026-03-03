<?php

namespace Modules\Core\Policies;

use Modules\Core\Models\Admin;

class AdminPolicy
{
    public function manageTokens(Admin $admin): bool
    {
        return $admin->can('core.token.manage');
    }
}
