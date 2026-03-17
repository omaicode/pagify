<?php

namespace Pagify\PageBuilder\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Pagify\Core\Http\Controllers\Api\ApiController;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Services\BlockRegistryService;

class AdminPageBuilderRegistryController extends ApiController
{
	public function registry(BlockRegistryService $blockRegistry): JsonResponse
	{
		$this->authorize('viewAny', Page::class);

		return $this->success([
			'blocks' => $blockRegistry->all(),
			'breakpoints' => array_values((array) config('page-builder.breakpoints', ['desktop', 'tablet', 'mobile'])),
		]);
	}
}
