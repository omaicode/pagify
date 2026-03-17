<?php

namespace Pagify\PageBuilder\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Pagify\Core\Http\Controllers\Api\ApiController;
use Pagify\PageBuilder\Services\EditorAccessTokenService;

class AdminPageBuilderEditorTokenVerifyController extends ApiController
{
	public function __invoke(Request $request, EditorAccessTokenService $editorAccessToken): JsonResponse
	{
		$validated = $request->validate([
			'token' => ['required', 'string', 'min:10'],
			'consume' => ['nullable', 'boolean'],
		]);

		$consume = (bool) ($validated['consume'] ?? false);

		try {
			$claims = $editorAccessToken->verify((string) $validated['token'], $consume);
		} catch (InvalidArgumentException) {
			return $this->error('Invalid editor access token.', 401, 'UNAUTHORIZED');
		}

		return $this->success([
			'valid' => true,
			'claims' => $claims,
		]);
	}
}
