<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Pagify\Content\Models\ContentEntry;
use Pagify\Content\Models\ContentType;
use Pagify\Core\Events\EntryPublished;
use Pagify\Core\Models\Admin;
use Pagify\Core\Services\EventBus;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ContentPublishingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_publish_now_updates_status_dispatches_event_and_creates_revision(): void
    {
        $capturedPublishedEntryId = null;
        app(EventBus::class)->subscribe(EntryPublished::class, static function (EntryPublished $event) use (&$capturedPublishedEntryId): void {
            $capturedPublishedEntryId = (int) $event->entryId;
        });

        $admin = $this->makeAdminWithPermissions([
            'content.entry.publish',
        ]);

        $contentType = $this->makeArticleType();

        $entry = ContentEntry::query()->create([
            'content_type_id' => $contentType->id,
            'slug' => 'publish-now-entry',
            'status' => 'draft',
            'data_json' => ['title' => 'Publish now'],
        ]);

        $response = $this->actingAs($admin, 'web')
            ->post('/admin/content/article/entries/' . $entry->id . '/publish');

        $response->assertRedirect('/admin/content/article/entries/' . $entry->id . '/edit');

        $entry->refresh();

        $this->assertSame('published', $entry->status);
        $this->assertNotNull($entry->published_at);
        $this->assertNull($entry->scheduled_publish_at);

        $this->assertSame($entry->id, $capturedPublishedEntryId);

        $this->assertDatabaseHas('content_entry_revisions', [
            'content_entry_id' => $entry->id,
            'action' => 'published',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'content.entry.published',
            'entity_type' => ContentEntry::class,
            'entity_id' => (string) $entry->id,
        ]);
    }

    public function test_schedule_and_command_process_publish_then_unpublish(): void
    {
        $admin = $this->makeAdminWithPermissions([
            'content.entry.publish',
        ]);

        $contentType = $this->makeArticleType();

        $entry = ContentEntry::query()->create([
            'content_type_id' => $contentType->id,
            'slug' => 'scheduled-entry',
            'status' => 'draft',
            'data_json' => ['title' => 'Scheduled'],
        ]);

        $base = Carbon::create(2026, 3, 4, 10, 0, 0);
        Carbon::setTestNow($base);

        try {
            $publishAt = $base->copy()->addMinutes(5);
            $unpublishAt = $base->copy()->addMinutes(10);

            $response = $this->actingAs($admin, 'web')
                ->post('/admin/content/article/entries/' . $entry->id . '/schedule', [
                    'scheduled_publish_at' => $publishAt->format('Y-m-d H:i:s'),
                    'scheduled_unpublish_at' => $unpublishAt->format('Y-m-d H:i:s'),
                ]);

            $response->assertRedirect('/admin/content/article/entries/' . $entry->id . '/edit');

            $entry->refresh();
            $this->assertSame('scheduled', $entry->status);
            $this->assertNotNull($entry->scheduled_publish_at);
            $this->assertNotNull($entry->scheduled_unpublish_at);

            Carbon::setTestNow($base->copy()->addMinutes(6));
            $this->artisan('content:publication:process')->assertExitCode(0);

            $entry->refresh();
            $this->assertSame('published', $entry->status);
            $this->assertNotNull($entry->published_at);

            Carbon::setTestNow($base->copy()->addMinutes(11));
            $this->artisan('content:publication:process')->assertExitCode(0);

            $entry->refresh();
            $this->assertSame('draft', $entry->status);
            $this->assertNotNull($entry->unpublished_at);

            $this->assertDatabaseHas('content_entry_revisions', [
                'content_entry_id' => $entry->id,
                'action' => 'scheduled',
            ]);

            $this->assertDatabaseHas('content_entry_revisions', [
                'content_entry_id' => $entry->id,
                'action' => 'auto_published',
            ]);

            $this->assertDatabaseHas('content_entry_revisions', [
                'content_entry_id' => $entry->id,
                'action' => 'auto_unpublished',
            ]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_publish_routes_are_permission_protected(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $contentType = $this->makeArticleType();

        $entry = ContentEntry::query()->create([
            'content_type_id' => $contentType->id,
            'slug' => 'protected-entry',
            'status' => 'draft',
            'data_json' => ['title' => 'Protected'],
        ]);

        $this->actingAs($admin, 'web')
            ->post('/admin/content/article/entries/' . $entry->id . '/publish')
            ->assertForbidden();

        $this->actingAs($admin, 'web')
            ->post('/admin/content/article/entries/' . $entry->id . '/unpublish')
            ->assertForbidden();

        $this->actingAs($admin, 'web')
            ->post('/admin/content/article/entries/' . $entry->id . '/schedule', [
                'scheduled_publish_at' => now()->addMinutes(5)->format('Y-m-d H:i:s'),
            ])
            ->assertForbidden();
    }

    private function makeArticleType(): ContentType
    {
        $contentType = ContentType::query()->create([
            'name' => 'Article',
            'slug' => 'article',
            'is_active' => true,
            'schema_json' => ['version' => 1, 'fields' => []],
        ]);

        $contentType->fields()->create([
            'key' => 'title',
            'label' => 'Title',
            'field_type' => 'text',
            'sort_order' => 0,
            'is_required' => true,
            'is_localized' => false,
        ]);

        return $contentType;
    }

    /**
     * @param array<int, string> $permissions
     */
    private function makeAdminWithPermissions(array $permissions): Admin
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        foreach ($permissions as $permissionName) {
            $permission = Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            $admin->givePermissionTo($permission);
        }

        return $admin;
    }
}
