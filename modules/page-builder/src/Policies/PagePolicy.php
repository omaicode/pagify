<?php

namespace Pagify\PageBuilder\Policies;

use Pagify\Core\Models\Admin;
use Pagify\PageBuilder\Models\Page;

class PagePolicy
{
	public function viewAny(Admin $admin): bool
	{
		return $admin->can('page-builder.page.viewAny');
	}

	public function view(Admin $admin, Page $page): bool
	{
		return $admin->can('page-builder.page.view') || $admin->can('page-builder.page.update');
	}

	public function create(Admin $admin): bool
	{
		return $admin->can('page-builder.page.create');
	}

	public function update(Admin $admin, Page $page): bool
	{
		return $admin->can('page-builder.page.update');
	}

	public function delete(Admin $admin, Page $page): bool
	{
		return $admin->can('page-builder.page.delete');
	}

	public function publish(Admin $admin, Page $page): bool
	{
		return $admin->can('page-builder.page.publish');
	}

	public function viewRevisions(Admin $admin, Page $page): bool
	{
		return $admin->can('page-builder.page.revision.view') || $admin->can('page-builder.page.update');
	}

	public function rollbackRevision(Admin $admin, Page $page): bool
	{
		return $admin->can('page-builder.page.revision.rollback') || $admin->can('page-builder.page.update');
	}
}
