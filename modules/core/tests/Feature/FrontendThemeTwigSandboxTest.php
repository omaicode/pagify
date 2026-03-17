<?php

namespace Pagify\Core\Tests\Feature;

use Illuminate\Support\Facades\File;
use Pagify\Core\Services\FrontendThemeTwigEngine;
use Tests\TestCase;

class FrontendThemeTwigSandboxTest extends TestCase
{
    private string $themePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themePath = base_path('storage/testing/themes/twig-sandbox');

        config()->set('core.frontend_ui.render.cache.enabled', false);
        config()->set('core.frontend_ui.render.sandbox.enabled', true);

        File::deleteDirectory($this->themePath);
        File::ensureDirectoryExists($this->themePath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->themePath);

        parent::tearDown();
    }

    public function test_sandbox_blocks_non_whitelisted_function(): void
    {
        File::put($this->themePath.'/blocked.twig', '<div>{{ random() }}</div>');

        $rendered = app(FrontendThemeTwigEngine::class)->render([$this->themePath], 'blocked.twig', [
            'locale' => 'en',
        ]);

        $this->assertNull($rendered);
    }

    public function test_sandbox_allows_registered_theme_helpers(): void
    {
        File::put($this->themePath.'/allowed.twig', '<a data-asset="{{ asset_url("robots.txt") }}"></a><span data-page="{{ helpers.url.page("landing") }}"></span>');

        $rendered = app(FrontendThemeTwigEngine::class)->render([$this->themePath], 'allowed.twig', [
            'locale' => 'en',
        ]);

        $this->assertIsString($rendered);
        $this->assertStringContainsString('/robots.txt', $rendered);
        $this->assertStringContainsString('/landing', $rendered);
    }
}
