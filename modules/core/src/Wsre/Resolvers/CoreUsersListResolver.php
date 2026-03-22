<?php

namespace Pagify\Core\Wsre\Resolvers;

use Illuminate\Support\Facades\Storage;
use Pagify\Core\Wsre\Contracts\WsreResolver;
use Pagify\Core\Models\Admin;
use Pagify\Core\Wsre\WsreRenderContext;
use Pagify\Core\Wsre\WsreResolverKeys;

class CoreUsersListResolver implements WsreResolver
{
	public function key(): string
	{
		return WsreResolverKeys::CORE_USERS_LIST;
	}

	/**
	 * @param array<string, mixed> $params
	 */
	public function resolve(array $params, WsreRenderContext $context): mixed
	{
		$limit = (int) ($params['limit'] ?? 12);
		$limit = max(1, min($limit, 50));
		$siteScoped = (bool) ($params['site_scoped'] ?? true);

		$query = Admin::query()
			->select(['id', 'site_id', 'name', 'email', 'avatar_path'])
			->orderBy('id', 'desc')
			->limit($limit);

		if ($siteScoped && $context->siteId() !== null) {
			$query->where('site_id', $context->siteId());
		}

		$admins = $query->get();
		$items = '';
		foreach ($admins as $admin) {
			$name = e((string) ($admin->name ?? 'Unknown'));
			$email = e((string) ($admin->email ?? ''));
			$avatarPath = trim((string) ($admin->avatar_path ?? ''));
			$avatarUrl = $avatarPath !== '' ? Storage::url($avatarPath) : '';
			$avatarTag = $avatarUrl !== ''
				? '<img class="wsre-user-avatar" src="' . e($avatarUrl) . '" alt="' . $name . '">'
				: '<span class="wsre-user-avatar wsre-user-avatar--placeholder" aria-hidden="true"></span>';

			$items .= '<li class="wsre-user-item">'
				. $avatarTag
				. '<div class="wsre-user-meta"><p class="wsre-user-name">' . $name . '</p><p class="wsre-user-email">' . $email . '</p></div>'
				. '</li>';
		}

		return '<section class="wsre-users"><ul class="wsre-users-list">' . $items . '</ul></section>';
	}
}
