<?php

namespace Pagify\Core\Http\Middleware;

use Closure;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Pagify\Core\Models\Site;
use Pagify\Core\Support\SiteContext;
use Symfony\Component\HttpFoundation\Response;

class ResolveSite
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $site = $this->resolveSite($request);

        if ($site !== null) {
            app(SiteContext::class)->setSite($site);
        }

        return $next($request);
    }

    private function resolveSite(Request $request): ?Site
    {
        $resolution = config('core.sites.resolution', []);
        $headerFallbackEnabled = (bool) ($resolution['enable_header_fallback'] ?? true);

        $candidates = collect();

        $host = trim((string) $request->getHost());
        if ($host !== '') {
            $candidates->push(['type' => 'domain', 'value' => $host]);
        }

        if ($headerFallbackEnabled) {
            $domainHeader = (string) ($resolution['header_domain'] ?? 'X-Site-Domain');
            $slugHeader = (string) ($resolution['header_slug'] ?? 'X-Site-Slug');

            $headerDomain = trim((string) $request->headers->get($domainHeader));
            if ($headerDomain !== '') {
                $candidates->push(['type' => 'domain', 'value' => $headerDomain]);
            }

            $headerSlug = trim((string) $request->headers->get($slugHeader));
            if ($headerSlug !== '') {
                $candidates->push(['type' => 'slug', 'value' => $headerSlug]);
            }
        }

        $defaultSlug = trim((string) ($resolution['default_site_slug'] ?? 'default'));
        if ($defaultSlug !== '') {
            $candidates->push(['type' => 'slug', 'value' => $defaultSlug]);
        }

        return $this->resolveFromCandidates($candidates);
    }

    /**
     * @param Collection<int, array{type:string,value:string}> $candidates
     */
    private function resolveFromCandidates(Collection $candidates): ?Site
    {
        foreach ($candidates->unique(fn (array $item): string => $item['type'] . '::' . $item['value']) as $candidate) {
            try {
                $query = Site::query()->where('is_active', true);

                if ($candidate['type'] === 'domain') {
                    $query->where('domain', $candidate['value']);
                }

                if ($candidate['type'] === 'slug') {
                    $query->where('slug', $candidate['value']);
                }

                $site = $query->first();
            } catch (Throwable) {
                return null;
            }

            if ($site !== null) {
                return $site;
            }
        }

        return null;
    }
}
