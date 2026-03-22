<?php

namespace Pagify\Core\Wsre\Contracts;

use Pagify\Core\Wsre\WsreRenderContext;

interface WsreResolver
{
	public function key(): string;

	/**
	 * @param array<string, mixed> $params
	 * @return mixed
	 */
	public function resolve(array $params, WsreRenderContext $context): mixed;
}
