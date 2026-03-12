<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pagify\Content\Database\Seeders\ContentDatabaseSeeder;
use Pagify\Content\Models\ContentEntry;
use Pagify\Content\Models\ContentType;
use Pagify\Core\Database\Seeders\CoreDatabaseSeeder;
use Pagify\Core\Models\Site;
use Pagify\PageBuilder\Database\Seeders\PageBuilderDatabaseSeeder;
use Pagify\PageBuilder\Models\Page;
use Tests\TestCase;

class UnifiedDefaultSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_builder_default_pages_are_seeded_and_idempotent(): void
    {
        $this->seed(CoreDatabaseSeeder::class);

        $this->seed(PageBuilderDatabaseSeeder::class);
        $this->seed(PageBuilderDatabaseSeeder::class);

        $site = Site::query()->where('slug', 'default')->firstOrFail();

        $slugs = Page::withoutGlobalScopes()
            ->where('site_id', $site->id)
            ->orderBy('slug')
            ->pluck('slug')
            ->all();

        $this->assertSame(['about', 'contact', 'docs', 'features', 'home'], $slugs);

        $home = Page::withoutGlobalScopes()->where('site_id', $site->id)->where('slug', 'home')->firstOrFail();
        $this->assertSame('published', $home->status);
        $this->assertNotNull($home->snapshot_generated_at);
        $this->assertStringContainsString('Build complete CMS websites in minutes', (string) $home->snapshot_html);
    }

    public function test_content_docs_seed_is_available_and_idempotent(): void
    {
        $this->seed(CoreDatabaseSeeder::class);

        $this->seed(ContentDatabaseSeeder::class);
        $this->seed(ContentDatabaseSeeder::class);

        $site = Site::query()->where('slug', 'default')->firstOrFail();

        $docsType = ContentType::withoutGlobalScopes()
            ->where('site_id', $site->id)
            ->where('slug', 'docs-page')
            ->firstOrFail();

        $this->assertTrue($docsType->is_active);

        $entries = ContentEntry::withoutGlobalScopes()
            ->where('site_id', $site->id)
            ->where('content_type_id', $docsType->id)
            ->orderBy('slug')
            ->get();

        $this->assertCount(3, $entries);
        $this->assertSame(['architecture-overview', 'operations-checklist', 'quickstart'], $entries->pluck('slug')->all());
        $this->assertSame(['published'], $entries->pluck('status')->unique()->values()->all());
    }
}
