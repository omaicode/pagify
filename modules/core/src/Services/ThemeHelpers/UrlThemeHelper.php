<?php

namespace Pagify\Core\Services\ThemeHelpers;

use Pagify\Core\Support\SiteContext;

class UrlThemeHelper
{
    public function __construct(
        private readonly SiteContext $siteContext,
    ) {
    }

    public function page(string $slug = ''): string
    {
        $normalized = trim($slug, '/');

        if ($normalized === '') {
            return url('/');
        }

        return url('/pages/'.$normalized);
    }

    public function site(string $path = ''): string
    {
        $site = $this->siteContext->site();
        $normalized = ltrim($path, '/');
        $appBase = rtrim((string) config('app.url', url('/')), '/');
        $appHost = parse_url($appBase, PHP_URL_HOST);
        $requestHost = $this->requestHost();
        $requestScheme = $this->requestScheme($appBase);

        if ($site !== null && (string) ($site->domain ?? '') !== '') {
            $host = (string) $site->domain;

            // When site domain is still local placeholder, prefer configured APP_URL.
            if ($this->isLocalHost($host)) {
                if (is_string($appHost) && $appHost !== '' && ! $this->isLocalHost($appHost)) {
                    return $normalized === '' ? $appBase : $appBase.'/'.$normalized;
                }

                if (is_string($requestHost) && $requestHost !== '' && ! $this->isLocalHost($requestHost)) {
                    $requestBase = sprintf('%s://%s', $requestScheme, $requestHost);

                    return $normalized === '' ? $requestBase : $requestBase.'/'.$normalized;
                }

                return $normalized === '' ? $appBase : $appBase.'/'.$normalized;
            }

            $scheme = $requestScheme;

            return $normalized === ''
                ? sprintf('%s://%s', $scheme, $host)
                : sprintf('%s://%s/%s', $scheme, $host, $normalized);
        }

        $base = $appBase;

        return $normalized === '' ? $base : $base.'/'.$normalized;
    }

    private function isLocalHost(string $host): bool
    {
        $normalized = trim(strtolower($host));

        if ($normalized === '') {
            return false;
        }

        if (str_contains($normalized, ':') && ! str_contains($normalized, ']')) {
            $normalized = explode(':', $normalized)[0];
        }

        return in_array($normalized, ['localhost', '127.0.0.1', '::1'], true);
    }

    private function requestHost(): string
    {
        $request = request();
        $candidates = [
            $request->header('x-forwarded-host'),
            $request->header('host'),
            $request->server('HTTP_HOST'),
            $_SERVER['HTTP_X_FORWARDED_HOST'] ?? null,
            $_SERVER['HTTP_HOST'] ?? null,
            $request->getHost(),
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            return trim(explode(':', $candidate)[0]);
        }

        return '';
    }

    private function requestScheme(string $appBase): string
    {
        $request = request();

        if ($request->isSecure()) {
            return 'https';
        }

        $candidates = [
            $request->header('x-forwarded-proto'),
            $request->server('REQUEST_SCHEME'),
            $request->server('HTTP_X_FORWARDED_PROTO'),
            $_SERVER['REQUEST_SCHEME'] ?? null,
            $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null,
            $request->getScheme(),
            parse_url($appBase, PHP_URL_SCHEME),
            'https',
        ];

        foreach ($candidates as $candidate) {
            if (! is_string($candidate) || trim($candidate) === '') {
                continue;
            }

            return strtolower(trim(explode(',', $candidate)[0]));
        }

        return 'https';
    }
}
