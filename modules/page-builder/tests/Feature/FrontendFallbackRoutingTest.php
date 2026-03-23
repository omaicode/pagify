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
use Pagify\PageBuilder\Services\PageService;
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

    public function test_wsre_template_is_prioritized_for_any_public_path(): void
    {
        File::put(base_path('storage/testing/themes/main/unified/pages/about.json'), json_encode([
            'body' => [[
                'tag' => 'main',
                'attrs' => ['data-source' => 'wsre'],
                'text' => 'About from wsre',
            ]],
        ], JSON_PRETTY_PRINT));

        Page::query()->create([
            'site_id' => Site::query()->where('slug', 'default')->value('id'),
            'title' => 'About PB',
            'slug' => 'about',
            'layout_json' => [
                'type' => 'webstudio',
                'webstudio' => [
                    'html' => '<main data-source="page-builder">About from page builder</main>',
                ],
            ],
        ]);

        $response = $this->get('/about');

        $response->assertOk();
        $response->assertSee('data-source="wsre"', false);
        $response->assertDontSee('data-source="page-builder"', false);
    }

    public function test_page_builder_is_used_when_theme_template_missing(): void
    {
        Page::query()->create([
            'site_id' => Site::query()->where('slug', 'default')->value('id'),
            'title' => 'Features PB',
            'slug' => 'features',
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

    public function test_page_builder_fallback_returns_html_document_without_twig(): void
    {
        Page::query()->create([
            'site_id' => Site::query()->where('slug', 'default')->value('id'),
            'title' => 'Contact PB',
            'slug' => 'contact',
            'layout_json' => [
                'type' => 'webstudio',
                'webstudio' => [
                    'html' => '<main data-source="page-builder">Contact from page builder</main>',
                    'css' => '.page-title{font-weight:700;}',
                ],
            ],
        ]);

        $response = $this->get('/contact');

        $response->assertOk();
        $response->assertSee('<!doctype html>', false);
        $response->assertSee('data-source="page-builder"', false);
    }

    public function test_wsre_can_use_event_bus_custom_resolver(): void
    {
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

    public function test_wsre_page_builder_resolver_renders_dynamic_variable_props(): void
    {
        $page = Page::query()->create([
            'site_id' => Site::query()->where('slug', 'default')->value('id'),
            'title' => 'Dynamic Link',
            'slug' => 'dynamic-link',
            'layout_json' => [],
        ]);

        app(PageService::class)->publish($page, null, [
            'page' => [
                'id' => (string) $page->id,
                'rootInstanceId' => 'root-' . (string) $page->id,
            ],
            'interface' => [
                'instances' => [
                    [
                        'id' => 'root-' . (string) $page->id,
                        'component' => 'ws:element',
                        'tag' => 'body',
                        'children' => [
                            ['type' => 'id', 'value' => 'link-' . (string) $page->id],
                        ],
                    ],
                    [
                        'id' => 'link-' . (string) $page->id,
                        'component' => 'ws:element',
                        'tag' => 'a',
                        'children' => [
                            ['type' => 'text', 'value' => 'Visit dynamic URL'],
                        ],
                    ],
                ],
                'props' => [
                    [
                        'instanceId' => 'link-' . (string) $page->id,
                        'name' => 'href',
                        'type' => 'parameter',
                        'value' => 'ds-link-' . (string) $page->id,
                    ],
                ],
                'styles' => [],
                'styleSourceSelections' => [],
                'breakpoints' => [],
                'dataSources' => [
                    [
                        'id' => 'ds-link-' . (string) $page->id,
                        'type' => 'variable',
                        'name' => 'Dynamic Link URL',
                        'value' => [
                            'type' => 'string',
                            'value' => 'https://example.test/dynamic-url',
                        ],
                    ],
                ],
                'resources' => [],
                'variableValues' => [
                    'ds-link-' . (string) $page->id => 'https://example.test/dynamic-url',
                ],
            ],
        ]);

        $response = $this->get('/dynamic-link');

        $response->assertOk();
        $response->assertSee('Visit dynamic URL', false);
        $response->assertSee('href="https://example.test/dynamic-url"', false);
    }
}
