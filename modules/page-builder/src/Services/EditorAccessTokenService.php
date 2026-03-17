<?php

namespace Pagify\PageBuilder\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EditorAccessTokenService
{
	/**
	 * @param array<string, mixed> $claims
	 * @return array{token: string, expires_at: string}
	 */
	public function issue(array $claims): array
	{
		$ttlSeconds = max(60, (int) config('page-builder.webstudio_iframe.token_ttl_seconds', 300));
		$now = Carbon::now();
		$expiresAt = $now->copy()->addSeconds($ttlSeconds);

		$payload = [
			'iss' => (string) config('page-builder.webstudio_iframe.token_issuer', config('app.url', 'pagify')),
			'aud' => (string) config('page-builder.webstudio_iframe.token_audience', 'webstudio-editor'),
			'iat' => $now->timestamp,
			'exp' => $expiresAt->timestamp,
			'jti' => (string) Str::uuid(),
			...$claims,
		];

		$header = ['alg' => 'HS256', 'typ' => 'JWT'];
		$encodedHeader = $this->base64UrlEncode((string) json_encode($header, JSON_UNESCAPED_SLASHES));
		$encodedPayload = $this->base64UrlEncode((string) json_encode($payload, JSON_UNESCAPED_SLASHES));
		$signingInput = $encodedHeader . '.' . $encodedPayload;

		$signature = hash_hmac('sha256', $signingInput, $this->resolveSecret(), true);
		$encodedSignature = $this->base64UrlEncode($signature);

		return [
			'token' => $signingInput . '.' . $encodedSignature,
			'expires_at' => $expiresAt->toIso8601String(),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function verify(string $token, bool $consume = false): array
	{
		$parts = explode('.', trim($token));

		if (count($parts) !== 3) {
			throw new InvalidArgumentException('Invalid token format.');
		}

		[$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

		$header = json_decode($this->base64UrlDecode($encodedHeader), true);
		$payload = json_decode($this->base64UrlDecode($encodedPayload), true);
		$signature = $this->base64UrlDecode($encodedSignature);

		if (! is_array($header) || ! is_array($payload) || ! is_string($signature)) {
			throw new InvalidArgumentException('Malformed token payload.');
		}

		if (($header['alg'] ?? null) !== 'HS256') {
			throw new InvalidArgumentException('Unsupported token algorithm.');
		}

		$expectedSignature = hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $this->resolveSecret(), true);

		if (! hash_equals($expectedSignature, $signature)) {
			throw new InvalidArgumentException('Invalid token signature.');
		}

		$now = Carbon::now()->timestamp;
		$exp = isset($payload['exp']) ? (int) $payload['exp'] : 0;

		if ($exp < $now) {
			throw new InvalidArgumentException('Token expired.');
		}

		$expectedIssuer = (string) config('page-builder.webstudio_iframe.token_issuer', config('app.url', 'pagify'));
		$expectedAudience = (string) config('page-builder.webstudio_iframe.token_audience', 'webstudio-editor');

		if (($payload['iss'] ?? null) !== $expectedIssuer) {
			throw new InvalidArgumentException('Invalid token issuer.');
		}

		if (($payload['aud'] ?? null) !== $expectedAudience) {
			throw new InvalidArgumentException('Invalid token audience.');
		}

		if ($consume && (bool) config('page-builder.webstudio_iframe.token_replay_protection', true)) {
			$jti = (string) ($payload['jti'] ?? '');

			if ($jti === '') {
				throw new InvalidArgumentException('Missing token id.');
			}

			$this->consumeTokenId($jti, $exp);
		}

		return $payload;
	}

	private function consumeTokenId(string $jti, int $exp): void
	{
		$ttl = max(1, $exp - Carbon::now()->timestamp);
		$cacheKeyPrefix = (string) config('page-builder.webstudio_iframe.token_replay_cache_prefix', 'page-builder:editor-token-jti:');
		$cacheKey = $cacheKeyPrefix . $jti;

		$wasAdded = Cache::add($cacheKey, 1, $ttl);

		if ($wasAdded !== true) {
			throw new InvalidArgumentException('Token replay detected.');
		}
	}

	private function resolveSecret(): string
	{
		$configuredSecret = trim((string) config('page-builder.webstudio_iframe.token_secret', ''));

		if ($configuredSecret !== '') {
			return $configuredSecret;
		}

		$appKey = (string) config('app.key', 'pagify-editor-token-fallback');

		if (str_starts_with($appKey, 'base64:')) {
			$decoded = base64_decode(substr($appKey, 7), true);

			if (is_string($decoded) && $decoded !== '') {
				return $decoded;
			}
		}

		return $appKey;
	}

	private function base64UrlEncode(string $value): string
	{
		return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
	}

	private function base64UrlDecode(string $value): string
	{
		$padding = 4 - (strlen($value) % 4);

		if ($padding < 4) {
			$value .= str_repeat('=', $padding);
		}

		$decoded = base64_decode(strtr($value, '-_', '+/'), true);

		if (! is_string($decoded)) {
			throw new InvalidArgumentException('Invalid base64url value.');
		}

		return $decoded;
	}
}
