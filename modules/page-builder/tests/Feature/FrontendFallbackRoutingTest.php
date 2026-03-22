<?php

namespace Pagify\PageBuilder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Pagify\Core\Models\Admin;
use Pagify\Core\Models\Site;
use Pagify\Core\Services\EventBus;
use Pagify\Core\Wsre\WsreHooks;
use Pagify\Core\Wsre\WsreResolverKeys;
use Pagify\PageBuilder\Models\Page;
use Tests\TestCase;

class FrontendFallbackRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        config()->set('core.frontend_ui.themes_base_path', 'storage/testing/themes/main');
        config()->set('core.frontend_ui.theme', 'unified');
        config()->set('core.frontend_ui.fallback_theme', 'unified');

        File::deleteDirectory(base_path('storage/testing/themes/main'));
        File::ensureDirectoryExists(base_path('storage/testing/themes/main/unified/layouts'));
        File::ensureDirectoryExists(base_path('storage/testing/themes/main/unified/pages'));

        File::put(base_path('storage/testing/themes/main/unified/theme.json'), json_encode([
            'slug' => 'unified',
            'name' => 'Unified',
            'version' => '1.0.0',
            'render' => [
                'engine' => 'twig',
            ],
            'layouts' => [
                ['file' => 'layouts/app.twig', 'label' => 'Main layout'],
            ],
        ], JSON_PRETTY_PRINT));

        File::put(base_path('storage/testing/themes/main/unified/layouts/app.twig'), "<!doctype html><html><head>{{ head|raw }}</head><body>{{ content|raw }}{% block body %}{% endblock %}</body></html>");
        File::put(base_path('storage/testing/themes/main/unified/pages/home.twig'), "{% extends 'layouts/app.twig' %}\n");

        Site::factory()->create([
            'slug' => 'default',
            'domain' => 'localhost',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path('storage/testing/themes/main'));

        parent::tearDown();
    }

    public function test_theme_template_is_prioritized_for_any_public_path(): void
    {
        File::put(base_path('storage/testing/themes/main/unified/pages/about.twig'), <<<'TWIG'
{% extends 'layouts/app.twig' %}
{% block body %}
<main data-source="theme-twig">About from theme twig</main>
{% endblock %}
TWIG
        );

        Page::query()->create([
            'site_id' => Site::query()->where('slug', 'default')->value('id'),
            'title' => 'About PB',
            'slug' => 'about',
            'status' => 'published',
            'layout_json' => [
                'type' => 'webstudio',
                'webstudio' => [
                    'html' => '<main data-source="page-builder">About from page builder</main>',
                ],
            ],
        ]);

        $response = $this->get('/about');

        $response->assertOk();
        $response->assertSee('data-source="theme-twig"', false);
        $response->assertDontSee('data-source="page-builder"', false);
    }

    public function test_page_builder_is_used_when_theme_template_missing(): void
    {
        Page::query()->create([
            'site_id' => Site::query()->where('slug', 'default')->value('id'),
            'title' => 'Features PB',
            'slug' => 'features',
            'status' => 'published',
            'layout_json' => [
                'type' => 'webstudio',
                'webstudio' => [
                    'html' => '<main data-source="page-builder">Features from page builder</main>',
                ],
            ],
        ]);

        $response = $this->get('/features');

        $response->assertOk();
        $response->assertSee('data-source="page-builder"', false);
    }

    public function test_contact_template_renders_without_twig_sandbox_failure(): void
    {
        File::put(base_path('storage/testing/themes/main/unified/pages/contact.twig'), <<<'TWIG'
{% extends 'layouts/app.twig' %}
{% block body %}
<main data-source="theme-contact">Admin link: {{ site_url(admin_prefix|default('admin')) }}</main>
{% endblock %}
TWIG
        );

        $response = $this->get('/contact');

        $response->assertOk();
        $response->assertSee('data-source="theme-contact"', false);
    }

    public function test_wsre_json_template_is_prioritized_for_public_path(): void
    {
        $this->enableWsreTheme();

        Page::query()->create([
            'site_id' => Site::query()->where('slug', 'default')->value('id'),
            'title' => 'About PB',
            'slug' => 'about',
            'status' => 'published',
            'layout_json' => [
                'type' => 'webstudio',
                'webstudio' => [
                    'html' => '<main data-source="page-builder">About from page builder</main>',
                ],
            ],
        ]);

        File::put(base_path('storage/testing/themes/main/unified/pages/about.json'), json_encode([
            'body' => [[
                'tag' => 'main',
                'attrs' => ['data-source' => 'wsre'],
                'text' => 'About from wsre',
            ]],
        ], JSON_PRETTY_PRINT));

        $response = $this->get('/about');

        $response->assertOk();
        $response->assertSee('data-source="wsre"', false);
        $response->assertDontSee('data-source="page-builder"', false);
    }

    public function test_wsre_can_use_event_bus_custom_resolver(): void
    {
        $this->enableWsreTheme();

        /** @var EventBus $eventBus */
        $eventBus = app(EventBus::class);
        $eventBus->onHook(WsreHooks::RESOLVERS, static fn (): array => [[
            'key' => 'demo.custom.users',
            'resolver' => static fn (array $params): string => '<section data-source="hook-resolver">count:' . (int) ($params['limit'] ?? 0) . '</section>',
        ]]);

        File::put(base_path('storage/testing/themes/main/unified/pages/team.json'), json_encode([
            'body' => [[
                'dynamic' => [
                    'resolver' => 'demo.custom.users',
                    'params' => ['limit' => 3],
                ],
            ]],
        ], JSON_PRETTY_PRINT));

        $response = $this->get('/team');

        $response->assertOk();
        $response->assertSee('data-source="hook-resolver"', false);
        $response->assertSee('count:3', false);
    }

    public function test_wsre_builtin_users_resolver_renders_site_scoped_admin_list(): void
    {
        $this->enableWsreTheme();

        $defaultSiteId = (int) Site::query()->where('slug', 'default')->value('id');
        $otherSite = Site::factory()->create([
            'slug' => 'other',
            'domain' => 'other.localhost',
            'is_active' => true,
        ]);

        Admin::factory()->create([
            'site_id' => $defaultSiteId,
            'name' => 'Default Admin',
            'email' => 'default-admin@example.test',
        ]);

        Admin::factory()->create([
            'site_id' => $otherSite->id,
            'name' => 'Other Admin',
            'email' => 'other-admin@example.test',
        ]);

        File::put(base_path('storage/testing/themes/main/unified/pages/members.json'), json_encode([
            'body' => [[
                'dynamic' => [
					'resolver' => WsreResolverKeys::CORE_USERS_LIST,
                    'params' => ['limit' => 10],
                ],
            ]],
        ], JSON_PRETTY_PRINT));

        $response = $this->get('/members');

        $response->assertOk();
        $response->assertSee('wsre-users-list', false);
        $response->assertSee('default-admin@example.test', false);
        $response->assertDontSee('other-admin@example.test', false);
    }

    private function enableWsreTheme(): void
    {
        File::put(base_path('storage/testing/themes/main/unified/theme.json'), json_encode([
            'slug' => 'unified',
            'name' => 'Unified',
            'version' => '1.0.0',
            'render' => [
                'engine' => 'wsre',
            ],
            'layouts' => [],
        ], JSON_PRETTY_PRINT));

        File::put(base_path('storage/testing/themes/main/unified/pages/home.json'), json_encode([
            'body' => [[
                'tag' => 'main',
                'attrs' => ['data-source' => 'wsre-home'],
                'text' => 'Home from wsre',
            ]],
        ], JSON_PRETTY_PRINT));
    }
}
