<?php

namespace Modules\Content\Policies;

use Modules\Content\Models\ContentEntry;
use Modules\Content\Models\ContentType;
use Modules\Core\Models\Admin;

class ContentEntryPolicy
{
    public function viewAny(Admin $admin, ContentType $contentType): bool
    {
        return $admin->can('content.entry.viewAny')
            || $admin->can('content.entry.view.' . $contentType->slug);
    }

    public function view(Admin $admin, ContentType $contentType, ContentEntry $entry): bool
    {
        return $entry->content_type_id === $contentType->id
            && (
                $admin->can('content.entry.view')
                || $admin->can('content.entry.view.' . $contentType->slug)
            );
    }

    public function create(Admin $admin, ContentType $contentType): bool
    {
        return $admin->can('content.entry.create')
            || $admin->can('content.entry.create.' . $contentType->slug);
    }

    public function update(Admin $admin, ContentType $contentType, ContentEntry $entry): bool
    {
        return $entry->content_type_id === $contentType->id
            && (
                $admin->can('content.entry.update')
                || $admin->can('content.entry.update.' . $contentType->slug)
            );
    }

    public function delete(Admin $admin, ContentType $contentType, ContentEntry $entry): bool
    {
        return $entry->content_type_id === $contentType->id
            && (
                $admin->can('content.entry.delete')
                || $admin->can('content.entry.delete.' . $contentType->slug)
            );
    }
}
