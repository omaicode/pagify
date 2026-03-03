<?php

namespace Modules\Core\Policies;

use Modules\Core\Models\Admin;
use Modules\Core\Models\Site;

class SitePolicy
{
    public function viewAny(Admin $admin): bool
    {
        return $admin->can('core.site.viewAny');
    }

    public function view(Admin $admin, Site $site): bool
    {
        return $admin->can('core.site.view') || $admin->site_id === $site->id;
    }

    public function create(Admin $admin): bool
    {
        return $admin->can('core.site.create');
    }

    public function update(Admin $admin, Site $site): bool
    {
        return $admin->can('core.site.update') || $admin->site_id === $site->id;
    }

    public function delete(Admin $admin, Site $site): bool
    {
        return $admin->can('core.site.delete') && $admin->site_id !== $site->id;
    }
}
