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

        if ($site !== null && (string) ($site->domain ?? '') !== '') {
            $scheme = request()->getScheme() ?: (parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https');
            $host = (string) $site->domain;

            return $normalized === ''
                ? sprintf('%s://%s', $scheme, $host)
                : sprintf('%s://%s/%s', $scheme, $host, $normalized);
        }

        $base = rtrim((string) config('app.url', url('/')), '/');

        return $normalized === '' ? $base : $base.'/'.$normalized;
    }
}
