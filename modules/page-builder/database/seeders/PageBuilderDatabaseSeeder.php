<?php

namespace Pagify\PageBuilder\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Pagify\Core\Models\Site;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Services\PageSnapshotService;

class PageBuilderDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::query()->where('is_active', true)->orderBy('id')->first()
            ?? Site::query()->orderBy('id')->first();

        if (! $site) {
            return;
        }

        /** @var PageSnapshotService $snapshotService */
        $snapshotService = app(PageSnapshotService::class);

        foreach ($this->seededPages() as $index => $payload) {
            $page = Page::withoutGlobalScopes()->updateOrCreate(
                [
                    'site_id' => $site->id,
                    'slug' => $payload['slug'],
                ],
                [
                    'title' => $payload['title'],
                    'status' => 'published',
                    'published_at' => Carbon::now()->subMinutes(10 - $index),
                    'layout_json' => [
                        'version' => 1,
                        'source' => 'seed:unified-default',
                        'grapes' => [
                            'html' => $payload['html'],
                            'css' => '',
                        ],
                    ],
                    'seo_meta_json' => [
                        'title' => $payload['seo_title'],
                        'description' => $payload['seo_description'],
                        'canonical_url' => url('/pages/'.$payload['slug']),
                    ],
                ]
            );

            $snapshotService->refresh($page);
        }
    }

    /**
     * @return array<int, array{slug: string, title: string, seo_title: string, seo_description: string, html: string}>
     */
    private function seededPages(): array
    {
        return [
            [
                'slug' => 'home',
                'title' => 'Pagify CMS',
                'seo_title' => 'Pagify CMS | Unified Default Theme',
                'seo_description' => 'Build modern content websites with Pagify CMS and the Unified default multipurpose theme.',
                'html' => <<<'HTML'
<section class="pf-panel overflow-hidden p-6 sm:p-10">
    <div class="grid gap-8 lg:grid-cols-[1.15fr,0.85fr] lg:items-center">
        <div>
            <span class="pf-badge">Pagify Unified</span>
            <h1 class="pf-heading mt-4 text-4xl font-extrabold leading-tight sm:text-5xl">Build complete CMS websites in minutes</h1>
            <p class="mt-4 text-base leading-8 text-[var(--text-soft)] sm:text-lg">Pagify combines modular architecture, visual page editing, content modeling, and multi-site control into one clean Laravel-powered CMS.</p>
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <a class="pf-cta-btn" href="/pages/features">Explore features</a>
                <a class="pf-cta-btn pf-cta-btn--ghost" href="/pages/docs">Read docs</a>
            </div>
        </div>
        <div class="pf-panel bg-[var(--surface-muted)] p-5">
            <h2 class="pf-heading text-xl font-bold">Why teams choose Pagify</h2>
            <ul class="mt-4 grid gap-3 text-sm text-[var(--text-soft)] sm:text-base">
                <li class="rounded-xl border border-[var(--line-soft)] bg-white px-4 py-3">Modular domains: core, content, media, page-builder, updater.</li>
                <li class="rounded-xl border border-[var(--line-soft)] bg-white px-4 py-3">Ready for production with permissions, audit logs, and queue-friendly workflows.</li>
                <li class="rounded-xl border border-[var(--line-soft)] bg-white px-4 py-3">Theme system with Twig sandbox and safe fallback rendering.</li>
            </ul>
        </div>
    </div>
</section>

<section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <article class="pf-panel p-5">
        <p class="text-sm font-semibold uppercase tracking-[0.08em] text-[var(--text-soft)]">Pages</p>
        <p class="pf-heading mt-2 text-3xl font-extrabold">5</p>
        <p class="mt-2 text-sm text-[var(--text-soft)]">Ready-to-publish marketing and docs pages.</p>
    </article>
    <article class="pf-panel p-5">
        <p class="text-sm font-semibold uppercase tracking-[0.08em] text-[var(--text-soft)]">Color presets</p>
        <p class="pf-heading mt-2 text-3xl font-extrabold">3</p>
        <p class="mt-2 text-sm text-[var(--text-soft)]">Ocean, Forest, Sunset with instant switch.</p>
    </article>
    <article class="pf-panel p-5">
        <p class="text-sm font-semibold uppercase tracking-[0.08em] text-[var(--text-soft)]">Responsive</p>
        <p class="pf-heading mt-2 text-3xl font-extrabold">100%</p>
        <p class="mt-2 text-sm text-[var(--text-soft)]">Mobile-first sections and adaptive cards.</p>
    </article>
    <article class="pf-panel p-5">
        <p class="text-sm font-semibold uppercase tracking-[0.08em] text-[var(--text-soft)]">Powered by</p>
        <p class="pf-heading mt-2 text-3xl font-extrabold">Laravel 12</p>
        <p class="mt-2 text-sm text-[var(--text-soft)]">Modern PHP stack with strong DX.</p>
    </article>
</section>
HTML,
            ],
            [
                'slug' => 'about',
                'title' => 'About Pagify',
                'seo_title' => 'About Pagify CMS',
                'seo_description' => 'Learn about Pagify CMS architecture, values, and delivery approach for teams.',
                'html' => <<<'HTML'
<section class="pf-panel p-6 sm:p-10">
    <span class="pf-badge">About</span>
    <h1 class="pf-heading mt-4 text-3xl font-extrabold sm:text-4xl">A modular CMS focused on practical delivery</h1>
    <p class="mt-4 max-w-3xl text-base leading-8 text-[var(--text-soft)]">Pagify is designed for teams that need a clear separation between content operations and product delivery. The platform enforces architecture contracts around permissions, auditability, and multi-site safety while staying easy to customize.</p>
    <div class="mt-6 grid gap-4 md:grid-cols-3">
        <article class="rounded-2xl border border-[var(--line-soft)] bg-[var(--surface-muted)] p-5">
            <h2 class="pf-heading text-xl font-bold">Mission</h2>
            <p class="mt-2 text-sm leading-7 text-[var(--text-soft)]">Give teams a robust CMS backbone with minimal setup overhead.</p>
        </article>
        <article class="rounded-2xl border border-[var(--line-soft)] bg-[var(--surface-muted)] p-5">
            <h2 class="pf-heading text-xl font-bold">Approach</h2>
            <p class="mt-2 text-sm leading-7 text-[var(--text-soft)]">Small, verifiable increments with testing and risk-aware rollout.</p>
        </article>
        <article class="rounded-2xl border border-[var(--line-soft)] bg-[var(--surface-muted)] p-5">
            <h2 class="pf-heading text-xl font-bold">Outcome</h2>
            <p class="mt-2 text-sm leading-7 text-[var(--text-soft)]">Faster feature delivery with predictable operations in production.</p>
        </article>
    </div>
</section>
HTML,
            ],
            [
                'slug' => 'features',
                'title' => 'Pagify Features',
                'seo_title' => 'Pagify CMS Features',
                'seo_description' => 'Discover key capabilities of Pagify CMS including page builder, content API, and multi-site governance.',
                'html' => <<<'HTML'
<section class="pf-panel p-6 sm:p-10">
    <span class="pf-badge">Features</span>
    <h1 class="pf-heading mt-4 text-3xl font-extrabold sm:text-4xl">Everything needed for modern CMS operations</h1>
    <p class="mt-4 max-w-3xl text-base leading-8 text-[var(--text-soft)]">Unified ships with common building blocks so teams can launch fast while keeping architecture boundaries clean.</p>
    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <article class="rounded-2xl border border-[var(--line-soft)] bg-white p-5">
            <h2 class="pf-heading text-xl font-bold">Visual Page Builder</h2>
            <p class="mt-2 text-sm leading-7 text-[var(--text-soft)]">Create and publish landing pages with revision history and snapshot rendering.</p>
        </article>
        <article class="rounded-2xl border border-[var(--line-soft)] bg-white p-5">
            <h2 class="pf-heading text-xl font-bold">Schema-driven Content</h2>
            <p class="mt-2 text-sm leading-7 text-[var(--text-soft)]">Model content types with field-level controls and publish workflow.</p>
        </article>
        <article class="rounded-2xl border border-[var(--line-soft)] bg-white p-5">
            <h2 class="pf-heading text-xl font-bold">Secure by default</h2>
            <p class="mt-2 text-sm leading-7 text-[var(--text-soft)]">Role-based permissions, policy enforcement, and audit logs across admin actions.</p>
        </article>
        <article class="rounded-2xl border border-[var(--line-soft)] bg-white p-5">
            <h2 class="pf-heading text-xl font-bold">Multi-site aware</h2>
            <p class="mt-2 text-sm leading-7 text-[var(--text-soft)]">Site isolation and locale-aware middleware for tenant-safe operations.</p>
        </article>
    </div>
</section>
HTML,
            ],
            [
                'slug' => 'docs',
                'title' => 'Pagify Docs',
                'seo_title' => 'Pagify CMS Documentation',
                'seo_description' => 'Quickstart and architecture docs for Pagify CMS.',
                'html' => <<<'HTML'
<section class="pf-panel p-6 sm:p-10">
    <span class="pf-badge">Documentation</span>
    <h1 class="pf-heading mt-4 text-3xl font-extrabold sm:text-4xl">Start building with Pagify docs</h1>
    <p class="mt-4 max-w-3xl text-base leading-8 text-[var(--text-soft)]">The docs content type is pre-seeded for you. You can access entries from the Content API and expand the knowledge base in the admin panel.</p>
    <div class="mt-6 rounded-2xl border border-[var(--line-soft)] bg-[var(--surface-muted)] p-5">
        <p class="text-sm text-[var(--text-soft)]">Docs API endpoint</p>
        <p class="mt-2 break-all font-mono text-sm font-semibold text-[var(--text-main)]">/api/v1/content/docs-page?status=published</p>
    </div>
    <div class="mt-6 grid gap-4 md:grid-cols-3">
        <article class="rounded-2xl border border-[var(--line-soft)] bg-white p-5">
            <h2 class="pf-heading text-lg font-bold">Quickstart</h2>
            <p class="mt-2 text-sm leading-7 text-[var(--text-soft)]">Install, configure site context, and publish your first page.</p>
        </article>
        <article class="rounded-2xl border border-[var(--line-soft)] bg-white p-5">
            <h2 class="pf-heading text-lg font-bold">Architecture</h2>
            <p class="mt-2 text-sm leading-7 text-[var(--text-soft)]">Understand modules, contracts, and runtime boundaries.</p>
        </article>
        <article class="rounded-2xl border border-[var(--line-soft)] bg-white p-5">
            <h2 class="pf-heading text-lg font-bold">Operations</h2>
            <p class="mt-2 text-sm leading-7 text-[var(--text-soft)]">Queue, publishing workflow, and production troubleshooting.</p>
        </article>
    </div>
</section>
HTML,
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact',
                'seo_title' => 'Contact Pagify Team',
                'seo_description' => 'Get in touch with the Pagify CMS team for support and collaboration.',
                'html' => <<<'HTML'
<section class="pf-panel p-6 sm:p-10">
    <span class="pf-badge">Contact</span>
    <h1 class="pf-heading mt-4 text-3xl font-extrabold sm:text-4xl">Talk with the Pagify team</h1>
    <p class="mt-4 max-w-3xl text-base leading-8 text-[var(--text-soft)]">Use this starter contact section as a baseline and wire your own backend endpoint when needed.</p>
    <div class="mt-6 grid gap-5 lg:grid-cols-[0.95fr,1.05fr]">
        <article class="rounded-2xl border border-[var(--line-soft)] bg-[var(--surface-muted)] p-5">
            <h2 class="pf-heading text-xl font-bold">Support channels</h2>
            <ul class="mt-3 grid gap-2 text-sm text-[var(--text-soft)]">
                <li>Email: support@pagify.local</li>
                <li>Docs: /pages/docs</li>
                <li>Admin: /admin</li>
            </ul>
        </article>
        <form class="rounded-2xl border border-[var(--line-soft)] bg-white p-5" method="post" action="#" aria-label="Contact form">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-sm font-semibold text-[var(--text-main)]">Name
                    <input class="mt-1 w-full rounded-xl border border-[var(--line-soft)] px-3 py-2" type="text" name="name" placeholder="Your name">
                </label>
                <label class="text-sm font-semibold text-[var(--text-main)]">Email
                    <input class="mt-1 w-full rounded-xl border border-[var(--line-soft)] px-3 py-2" type="email" name="email" placeholder="you@example.com">
                </label>
            </div>
            <label class="mt-4 block text-sm font-semibold text-[var(--text-main)]">Message
                <textarea class="mt-1 w-full rounded-xl border border-[var(--line-soft)] px-3 py-2" name="message" rows="5" placeholder="How can we help?"></textarea>
            </label>
            <button class="pf-cta-btn mt-4" type="submit">Send message</button>
        </form>
    </div>
</section>
HTML,
            ],
        ];
    }
}
