<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Pagify\Core\Models\Setting;
use Pagify\Core\Models\Site;
use Pagify\PageBuilder\Models\Page;
use Tests\TestCase;

class PageBuilderThemeRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        config()->set('core.frontend_ui.themes_base_path', 'storage/testing/themes/main');
        config()->set('core.frontend_ui.theme', 'default');
        config()->set('core.frontend_ui.fallback_theme', 'default');

        File::deleteDirectory(base_path('storage/testing/themes/main'));
        File::ensureDirectoryExists(base_path('storage/testing/themes/main/default/layouts'));
        File::ensureDirectoryExists(base_path('storage/testing/themes/main/default/pages'));

        File::put(base_path('storage/testing/themes/main/default/theme.json'), json_encode([
            'slug' => 'default',
            'name' => 'Default Main Theme',
            'version' => '1.0.0',
            'render' => [
                'engine' => 'twig',
            ],
        ], JSON_PRETTY_PRINT));

        File::put(base_path('storage/testing/themes/main/default/layouts/app.twig'), "<!doctype html><html><head>{{ head|raw }}</head><body><div data-theme=\"default\">{{ content|raw }}</div><a data-asset=\"{{ asset_url('robots.txt') }}\"></a><span data-locale=\"{{ locale }}\"></span></body></html>");
        File::put(base_path('storage/testing/themes/main/default/pages/page.twig'), "{% extends 'layouts/app.twig' %}\n");

        File::ensureDirectoryExists(base_path('storage/testing/themes/main/ocean/layouts'));
        File::ensureDirectoryExists(base_path('storage/testing/themes/main/ocean/pages'));

        File::put(base_path('storage/testing/themes/main/ocean/theme.json'), json_encode([
            'slug' => 'ocean',
            'name' => 'Ocean',
            'version' => '1.0.0',
            'render' => [
                'engine' => 'twig',
            ],
        ], JSON_PRETTY_PRINT));

        File::put(base_path('storage/testing/themes/main/ocean/layouts/app.twig'), "<!doctype html><html><head>{{ head|raw }}</head><body><div data-theme=\"ocean\">{{ content|raw }}</div><span data-site-url=\"{{ helpers.url.site('hello') }}\"></span></body></html>");
        File::put(base_path('storage/testing/themes/main/ocean/pages/page.twig'), "{% extends 'layouts/app.twig' %}\n");

        File::ensureDirectoryExists(base_path('storage/testing/themes/main/broken/layouts'));
        File::ensureDirectoryExists(base_path('storage/testing/themes/main/broken/pages'));
        File::put(base_path('storage/testing/themes/main/broken/theme.json'), json_encode([
            'slug' => 'wrong-slug',
            'name' => 'Broken',
            'version' => '1.0.0',
            'render' => [
                'engine' => 'twig',
            ],
        ], JSON_PRETTY_PRINT));
        File::put(base_path('storage/testing/themes/main/broken/layouts/app.twig'), "<!doctype html><html><head>{{ head|raw }}</head><body><div data-theme=\"broken\">{{ content|raw }}</div></body></html>");
        File::put(base_path('storage/testing/themes/main/broken/pages/page.twig'), "{% extends 'layouts/app.twig' %}\n");
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path('storage/testing/themes/main'));

        parent::tearDown();
    }

    public function test_public_page_uses_active_theme_for_current_site(): void
    {
        $site = Site::factory()->create([
            'slug' => 'default',
            'domain' => 'localhost',
            'is_active' => true,
        ]);

        Setting::query()->create([
            'site_id' => $site->id,
            'key' => 'theme.main.active',
            'value' => 'ocean',
        ]);

        Page::query()->create([
            'site_id' => $site->id,
            'title' => 'Landing',
            'slug' => 'landing',
            'status' => 'published',
            'layout_json' => ['sections' => []],
            'seo_meta_json' => ['title' => 'Landing SEO'],
            'snapshot_html' => '<!doctype html><html><head><style>.demo{color:red;}</style></head><body><main id="demo-body">Hello Theme</main></body></html>',
        ]);

        $response = $this->get('/pages/landing');

        $response->assertOk();
        $response->assertSee('data-theme="ocean"', false);
        $response->assertSee('<main id="demo-body">Hello Theme</main>', false);
        $response->assertSee('data-site-url="http://localhost/hello"', false);
    }

    public function test_fallback_uses_configured_fallback_when_active_theme_is_invalid(): void
    {
        config()->set('core.frontend_ui.fallback_theme', 'ocean');

        $site = Site::factory()->create([
            'slug' => 'default',
            'domain' => 'localhost',
            'is_active' => true,
        ]);

        Setting::query()->create([
            'site_id' => $site->id,
            'key' => 'theme.main.active',
            'value' => 'broken',
        ]);

        Page::query()->create([
            'site_id' => $site->id,
            'title' => 'Pricing',
            'slug' => 'pricing',
            'status' => 'published',
            'layout_json' => ['sections' => []],
            'seo_meta_json' => ['title' => 'Pricing SEO'],
            'snapshot_html' => '<!doctype html><html><head></head><body><main>Fallback chain</main></body></html>',
        ]);

        $response = $this->get('/pages/pricing');

        $response->assertOk();
        $response->assertSee('data-theme="ocean"', false);
        $response->assertDontSee('data-theme="broken"', false);
    }

    public function test_fallback_uses_default_theme_when_active_and_fallback_page_view_missing(): void
    {
        config()->set('core.frontend_ui.fallback_theme', 'ocean');
        File::put(base_path('storage/testing/themes/main/ocean/theme.json'), json_encode([
            'slug' => 'ocean-broken',
            'name' => 'Ocean Broken',
            'version' => '1.0.0',
            'render' => [
                'engine' => 'twig',
            ],
        ], JSON_PRETTY_PRINT));

        $site = Site::factory()->create([
            'slug' => 'default',
            'domain' => 'localhost',
            'is_active' => true,
        ]);

        Setting::query()->create([
            'site_id' => $site->id,
            'key' => 'theme.main.active',
            'value' => 'missing-active-theme',
        ]);

        Page::query()->create([
            'site_id' => $site->id,
            'title' => 'About',
            'slug' => 'about',
            'status' => 'published',
            'layout_json' => ['sections' => []],
            'seo_meta_json' => ['title' => 'About SEO'],
            'snapshot_html' => '<!doctype html><html><head></head><body><main>Default fallback</main></body></html>',
        ]);

        $response = $this->get('/pages/about');

        $response->assertOk();
        $response->assertSee('data-theme="default"', false);
        $response->assertDontSee('data-theme="ocean"', false);
    }
}
