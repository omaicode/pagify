<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Pagify\Core\Models\Site;
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
            'layout_json' => ['sections' => []],
            'snapshot_html' => '<!doctype html><html><head></head><body><main data-source="page-builder">About from page builder</main></body></html>',
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
            'layout_json' => ['sections' => []],
            'snapshot_html' => '<!doctype html><html><head></head><body><main data-source="page-builder">Features from page builder</main></body></html>',
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
}
