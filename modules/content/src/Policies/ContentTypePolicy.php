<?php

namespace Pagify\Content\Policies;

use Pagify\Content\Models\ContentType;
use Pagify\Core\Models\Admin;

class ContentTypePolicy
{
    public function viewAny(Admin $admin): bool
    {
        return $admin->can('content.type.viewAny');
    }

    public function view(Admin $admin, ContentType $contentType): bool
    {
        return $admin->can('content.type.viewAny')
            || $admin->can('content.type.view.' . $contentType->slug);
    }

    public function create(Admin $admin): bool
    {
        return $admin->can('content.type.create');
    }

    public function update(Admin $admin, ContentType $contentType): bool
    {
        return $admin->can('content.type.update')
            || $admin->can('content.type.update.' . $contentType->slug);
    }

    public function delete(Admin $admin, ContentType $contentType): bool
    {
        return $admin->can('content.type.delete')
            || $admin->can('content.type.delete.' . $contentType->slug);
    }
}
