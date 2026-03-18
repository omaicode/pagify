<?php

namespace Pagify\PageBuilder\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Pagify\Core\Http\Controllers\Api\ApiController;
use Pagify\Core\Support\SiteContext;
use Pagify\PageBuilder\Services\EditorAccessTokenService;
use Pagify\PageBuilder\Services\WebstudioTrpcService;

class WebstudioTrpcController extends ApiController
{
	public function __invoke(Request $request, EditorAccessTokenService $editorAccessToken, SiteContext $siteContext, WebstudioTrpcService $trpcService): JsonResponse
	{
		return $trpcService->handle($request, $editorAccessToken, $siteContext);
	}
}
