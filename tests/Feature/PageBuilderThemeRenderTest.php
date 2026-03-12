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
        File::ensureDirectoryExists(base_path('storage/testing/themes/main/default/resources/views/layouts'));
        File::ensureDirectoryExists(base_path('storage/testing/themes/main/default/resources/views/pages'));

        File::put(base_path('storage/testing/themes/main/default/theme.json'), json_encode([
            'slug' => 'default',
            'name' => 'Default Main Theme',
            'version' => '1.0.0',
        ], JSON_PRETTY_PRINT));

        File::put(base_path('storage/testing/themes/main/default/resources/views/layouts/app.blade.php'), "<!doctype html><html><head>@yield('head')</head><body><div data-theme=\"default\">@yield('content')</div></body></html>");
        File::put(base_path('storage/testing/themes/main/default/resources/views/pages/page.blade.php'), "@extends('layouts.app')\n@section('head'){!! \$head ?? '' !!}@endsection\n@section('content'){!! \$content ?? '' !!}@endsection\n");

        File::ensureDirectoryExists(base_path('storage/testing/themes/main/ocean/resources/views/layouts'));
        File::ensureDirectoryExists(base_path('storage/testing/themes/main/ocean/resources/views/pages'));

        File::put(base_path('storage/testing/themes/main/ocean/theme.json'), json_encode([
            'slug' => 'ocean',
            'name' => 'Ocean',
            'version' => '1.0.0',
        ], JSON_PRETTY_PRINT));

        File::put(base_path('storage/testing/themes/main/ocean/resources/views/layouts/app.blade.php'), "<!doctype html><html><head>@yield('head')</head><body><div data-theme=\"ocean\">@yield('content')</div></body></html>");
        File::put(base_path('storage/testing/themes/main/ocean/resources/views/pages/page.blade.php'), "@extends('layouts.app')\n@section('head'){!! \$head ?? '' !!}@endsection\n@section('content'){!! \$content ?? '' !!}@endsection\n");
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
    }
}
