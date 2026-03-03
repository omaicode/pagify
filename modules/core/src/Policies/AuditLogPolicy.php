<?php

namespace Modules\Core\Policies;

use Modules\Core\Models\Admin;
use Modules\Core\Models\AuditLog;

class AuditLogPolicy
{
    public function viewAny(Admin $admin): bool
    {
        return $admin->can('core.audit.viewAny') || $admin->can('core.audit.view');
    }

    public function view(Admin $admin, AuditLog $auditLog): bool
    {
        return $admin->can('core.audit.view') || $admin->site_id === $auditLog->site_id;
    }
}
