<?php

namespace Pagify\PageBuilder\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Pagify\Core\Http\Controllers\Api\ApiController;

class AdminPageBuilderEditorContractController extends ApiController
{
	public function __invoke(): JsonResponse
	{
		$contract = (array) config('page-builder.webstudio_iframe_contract', []);

		return $this->success([
			'contract' => $contract,
			'endpoints' => [
				'token_access' => route('page-builder.api.v1.admin.editor.access-token'),
				'token_verify' => route('page-builder.api.v1.admin.editor.verify-token'),
			],
		]);
	}
}
