<?php

namespace Pagify\PageBuilder\Policies;

use Pagify\Core\Models\Admin;
use Pagify\PageBuilder\Models\PageTemplate;
use Pagify\PageBuilder\Models\SectionTemplate;

class PageBuilderLibraryPolicy
{
	public function viewAny(Admin $admin): bool
	{
		return $admin->can('page-builder.library.manage') || $admin->can('page-builder.page.viewAny');
	}

	public function view(Admin $admin, PageTemplate|SectionTemplate $resource): bool
	{
		return $this->viewAny($admin);
	}

	public function create(Admin $admin): bool
	{
		return $admin->can('page-builder.library.manage');
	}

	public function update(Admin $admin, PageTemplate|SectionTemplate $resource): bool
	{
		return $admin->can('page-builder.library.manage');
	}

	public function delete(Admin $admin, PageTemplate|SectionTemplate $resource): bool
	{
		return $admin->can('page-builder.library.manage');
	}
}
