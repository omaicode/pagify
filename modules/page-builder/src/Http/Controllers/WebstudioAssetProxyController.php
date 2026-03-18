<?php

namespace Pagify\PageBuilder\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WebstudioAssetProxyController extends Controller
{
	public function image(Request $request, string $path = ''): RedirectResponse
	{
		return $this->redirectToAssetSource($request, $path);
	}

	public function asset(Request $request, string $path = ''): RedirectResponse
	{
		return $this->redirectToAssetSource($request, $path);
	}

	private function redirectToAssetSource(Request $request, string $path): RedirectResponse
	{
		$decoded = rawurldecode(trim($path));

		if ($decoded === '' || str_contains($decoded, '..')) {
			abort(404);
		}

		if (filter_var($decoded, FILTER_VALIDATE_URL)) {
			$target = $this->normalizeAbsoluteUrl($request, $decoded);

			return redirect()->to($target);
		}

		$normalizedPath = '/' . ltrim($decoded, '/');

		return redirect()->to($normalizedPath);
	}

	private function normalizeAbsoluteUrl(Request $request, string $url): string
	{
		$parts = parse_url($url);

		if (! is_array($parts)) {
			abort(404);
		}

		$targetHost = strtolower((string) ($parts['host'] ?? ''));
		$requestHost = strtolower((string) $request->getHost());

		if ($targetHost === '' || $targetHost !== $requestHost) {
			abort(403);
		}

		$scheme = (string) ($parts['scheme'] ?? $request->getScheme());
		$path = (string) ($parts['path'] ?? '/');
		$query = isset($parts['query']) && is_string($parts['query']) && $parts['query'] !== ''
			? '?' . $parts['query']
			: '';

		return $scheme . '://' . $targetHost . $path . $query;
	}
}
