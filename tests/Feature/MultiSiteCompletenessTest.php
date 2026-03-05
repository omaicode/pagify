<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Pagify\Core\Http\Middleware\ResolveSite;
use Pagify\Core\Models\Setting;
use Pagify\Core\Models\Site;
use Pagify\Core\Support\SiteContext;
use Tests\TestCase;

class MultiSiteCompletenessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->requiresSqliteButUnavailable()) {
            $this->markTestSkipped('pdo_sqlite is required for multi-site DB tests in this environment.');
        }

        $this->artisan('migrate', ['--force' => true]);
    }

    public function test_resolve_site_supports_header_fallback_then_default_slug(): void
    {
        $tenant = Site::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'domain' => 'tenant-a.localhost',
            'locale' => 'en',
            'is_active' => true,
        ]);

        Site::query()->create([
            'name' => 'Default Site',
            'slug' => 'default',
            'domain' => 'localhost',
            'locale' => 'en',
            'is_active' => true,
        ]);

        $middleware = new ResolveSite();
        $siteContext = app(SiteContext::class);

        $requestByHeader = Request::create('http://unknown.local/admin', 'GET', [], [], [], [
            'HTTP_X_SITE_SLUG' => 'tenant-a',
        ]);

        $middleware->handle($requestByHeader, static function () {
            return response('ok');
        });

        $this->assertSame($tenant->id, $siteContext->siteId());

        $siteContext->setSite(null);

        $requestByDefault = Request::create('http://unknown.local/admin', 'GET');

        $middleware->handle($requestByDefault, static function () {
            return response('ok');
        });

        $this->assertSame('default', $siteContext->site()?->slug);
    }

    public function test_setting_scope_enforces_site_isolation_and_keeps_global_values(): void
    {
        $siteA = Site::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'domain' => 'tenant-a.localhost',
            'locale' => 'en',
            'is_active' => true,
        ]);

        $siteB = Site::query()->create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'domain' => 'tenant-b.localhost',
            'locale' => 'en',
            'is_active' => true,
        ]);

        app(SiteContext::class)->setSite($siteA);

        Setting::query()->create([
            'key' => 'site_only',
            'value' => ['v' => 'a'],
        ]);

        Setting::withoutGlobalScopes()->create([
            'site_id' => $siteB->id,
            'key' => 'other_site',
            'value' => ['v' => 'b'],
        ]);

        Setting::withoutGlobalScopes()->create([
            'site_id' => null,
            'key' => 'global_setting',
            'value' => ['v' => 'g'],
        ]);

        $visibleKeys = Setting::query()->pluck('key')->all();

        $this->assertContains('site_only', $visibleKeys);
        $this->assertContains('global_setting', $visibleKeys);
        $this->assertNotContains('other_site', $visibleKeys);

        $created = Setting::query()->create([
            'key' => 'auto_site_id',
            'value' => ['v' => 1],
        ]);

        $this->assertSame($siteA->id, $created->site_id);
    }

    private function requiresSqliteButUnavailable(): bool
    {
        return config('database.default') === 'sqlite' && ! extension_loaded('pdo_sqlite');
    }
}
