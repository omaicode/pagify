<?php

namespace Pagify\Core\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Pagify\Core\Services\ThemeHelpers\AssetThemeHelper;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class FrontendThemeAssetExposureTest extends TestCase
{
    use RefreshDatabase;

    private string $themeRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themeRoot = base_path('storage/testing/themes/main/unified-assets');

        config()->set('core.frontend_ui.themes_base_path', 'storage/testing/themes/main');

        File::deleteDirectory(base_path('storage/testing/themes/main'));
        File::ensureDirectoryExists($this->themeRoot.'/assets/css');
        File::ensureDirectoryExists($this->themeRoot.'/layouts');
        File::ensureDirectoryExists($this->themeRoot.'/pages');

        File::put($this->themeRoot.'/theme.json', json_encode([
            'slug' => 'unified-assets',
            'name' => 'Unified Assets',
            'version' => '1.0.0',
            'render' => [
                'engine' => 'twig',
            ],
            'layouts' => [
                ['file' => 'layouts/app.twig', 'label' => 'Main layout'],
            ],
        ], JSON_PRETTY_PRINT));

        File::put($this->themeRoot.'/layouts/app.twig', '<!doctype html><html><body>{{ content|raw }}</body></html>');
        File::put($this->themeRoot.'/pages/home.twig', '{% extends "layouts/app.twig" %}');
        File::put($this->themeRoot.'/assets/css/site.css', 'body{color:#111;}');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(base_path('storage/testing/themes/main'));

        parent::tearDown();
    }

    public function test_theme_asset_endpoint_serves_asset_file_for_allowed_directory(): void
    {
        $response = $this->get('/theme-assets/unified-assets/css/site.css');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/css; charset=utf-8');

        $baseResponse = $response->baseResponse;
        $this->assertInstanceOf(BinaryFileResponse::class, $baseResponse);
        $cacheControl = strtolower((string) $baseResponse->headers->get('cache-control', ''));
        $this->assertStringContainsString('max-age=3600', $cacheControl);
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertSame(
            realpath($this->themeRoot.'/assets/css/site.css'),
            $baseResponse->getFile()?->getRealPath(),
        );
    }

    public function test_theme_asset_endpoint_blocks_disallowed_directory(): void
    {
        File::ensureDirectoryExists($this->themeRoot.'/assets/fonts');
        File::put($this->themeRoot.'/assets/fonts/app.woff2', 'binary');

        $response = $this->get('/theme-assets/unified-assets/fonts/app.woff2');

        $response->assertNotFound();
    }

    public function test_theme_asset_endpoint_blocks_directory_traversal(): void
    {
        $response = $this->get('/theme-assets/unified-assets/css/%2e%2e/%2e%2e/theme.json');

        $response->assertNotFound();
    }

    public function test_asset_url_helper_appends_mtime_cache_busting_query(): void
    {
        config()->set('core.frontend_ui.assets.cache_busting.strategy', 'mtime');

        $helper = app(AssetThemeHelper::class);
        $mtime = filemtime($this->themeRoot.'/assets/css/site.css');

        $url = $helper->url('css/site.css', 'unified-assets');

        $this->assertIsInt($mtime);
        $this->assertStringContainsString('/theme-assets/unified-assets/css/site.css', $url);
        $this->assertStringContainsString('v='.$mtime, $url);
    }

    public function test_asset_url_helper_appends_hash_cache_busting_query(): void
    {
        config()->set('core.frontend_ui.assets.cache_busting.strategy', 'hash');
        config()->set('core.frontend_ui.assets.cache_busting.hash_length', 10);

        $helper = app(AssetThemeHelper::class);
        $expectedHash = substr((string) sha1_file($this->themeRoot.'/assets/css/site.css'), 0, 10);

        $url = $helper->url('css/site.css', 'unified-assets');

        $this->assertStringContainsString('/theme-assets/unified-assets/css/site.css', $url);
        $this->assertStringContainsString('v='.$expectedHash, $url);
    }

    public function test_asset_url_helper_skips_cache_busting_when_strategy_is_off(): void
    {
        config()->set('core.frontend_ui.assets.cache_busting.strategy', 'off');

        $helper = app(AssetThemeHelper::class);
        $url = $helper->url('css/site.css', 'unified-assets');

        $this->assertStringContainsString('/theme-assets/unified-assets/css/site.css', $url);
        $this->assertStringNotContainsString('?v=', $url);
    }
}
