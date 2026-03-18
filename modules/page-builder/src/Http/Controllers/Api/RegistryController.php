<?php

namespace Pagify\PageBuilder\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pagify\Core\Http\Controllers\Api\ApiController;
use Pagify\Core\Support\SiteContext;
use Pagify\PageBuilder\Http\Controllers\Api\Concerns\InteractsWithWebstudioProjectState;
use Pagify\PageBuilder\Services\BlockRegistryService;
use Pagify\PageBuilder\Services\EditorAccessTokenService;

class RegistryController extends ApiController
{
	use InteractsWithWebstudioProjectState;

	public function registry(Request $request, BlockRegistryService $blockRegistry, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext): JsonResponse
	{
		$claims = $this->authorizeRequest($request, $editorAccessToken, $siteContext, 'page:read');
		if ($claims instanceof JsonResponse) {
			return $claims;
		}

		return $this->success([
			'blocks' => $blockRegistry->all(),
			'breakpoints' => array_values((array) config('page-builder.breakpoints', ['desktop', 'tablet', 'mobile'])),
		]);
	}
}
