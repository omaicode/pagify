<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Content\Models\ContentEntry;
use Modules\Content\Models\ContentEntryRevision;
use Modules\Content\Models\ContentType;
use Modules\Core\Models\Admin;
use Tests\TestCase;

class ContentPermissionDeniedMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_admin_and_api_routes_are_permission_protected(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $contentType = ContentType::factory()->create([
            'slug' => 'article',
            'name' => 'Article',
        ]);

        $contentType->fields()->create([
            'key' => 'title',
            'label' => 'Title',
            'field_type' => 'text',
            'sort_order' => 0,
            'is_required' => true,
            'is_localized' => false,
        ]);

        $entry = ContentEntry::factory()->create([
            'content_type_id' => $contentType->id,
            'slug' => 'entry-no-perm',
            'data_json' => ['title' => 'No permission'],
        ]);

        $revision = ContentEntryRevision::factory()->create([
            'content_entry_id' => $entry->id,
            'revision_no' => 1,
            'action' => 'created',
            'snapshot_json' => [
                'slug' => $entry->slug,
                'status' => $entry->status,
                'data' => $entry->data_json,
            ],
        ]);

        $this->actingAs($admin, 'web')->get('/admin/content/types')->assertForbidden();
        $this->actingAs($admin, 'web')->get('/admin/content/types/create')->assertForbidden();
        $this->actingAs($admin, 'web')->post('/admin/content/types', [
            'name' => 'Blocked Type',
            'slug' => 'blocked-type',
            'fields' => [[
                'key' => 'title',
                'label' => 'Title',
                'field_type' => 'text',
            ]],
        ])->assertForbidden();

        $this->actingAs($admin, 'web')->get('/admin/content/types/' . $contentType->id . '/edit')->assertForbidden();
        $this->actingAs($admin, 'web')->put('/admin/content/types/' . $contentType->id, [
            'name' => 'Article Updated',
            'slug' => 'article',
            'fields' => [[
                'key' => 'title',
                'label' => 'Title',
                'field_type' => 'text',
            ]],
        ])->assertForbidden();
        $this->actingAs($admin, 'web')->delete('/admin/content/types/' . $contentType->id)->assertForbidden();

        $this->actingAs($admin, 'web')->get('/admin/content/types/' . $contentType->id . '/builder')->assertForbidden();
        $this->actingAs($admin, 'web')->put('/admin/content/types/' . $contentType->id . '/builder', [
            'fields' => [[
                'key' => 'title',
                'label' => 'Title',
                'field_type' => 'text',
            ]],
        ])->assertForbidden();
        $this->actingAs($admin, 'web')->get('/admin/content/types/' . $contentType->id . '/builder/status')->assertForbidden();

        $this->actingAs($admin, 'web')->get('/admin/content/article/entries')->assertForbidden();
        $this->actingAs($admin, 'web')->get('/admin/content/article/entries/create')->assertForbidden();
        $this->actingAs($admin, 'web')->post('/admin/content/article/entries', [
            'slug' => 'blocked-entry',
            'data' => ['title' => 'Blocked'],
        ])->assertForbidden();

        $this->actingAs($admin, 'web')->get('/admin/content/article/entries/' . $entry->id . '/edit')->assertForbidden();
        $this->actingAs($admin, 'web')->put('/admin/content/article/entries/' . $entry->id, [
            'slug' => 'blocked-entry',
            'data' => ['title' => 'Blocked'],
        ])->assertForbidden();
        $this->actingAs($admin, 'web')->delete('/admin/content/article/entries/' . $entry->id)->assertForbidden();

        $this->actingAs($admin, 'web')->post('/admin/content/article/entries/' . $entry->id . '/publish')->assertForbidden();
        $this->actingAs($admin, 'web')->post('/admin/content/article/entries/' . $entry->id . '/unpublish')->assertForbidden();
        $this->actingAs($admin, 'web')->post('/admin/content/article/entries/' . $entry->id . '/schedule', [
            'scheduled_publish_at' => now()->addMinutes(10)->toDateTimeString(),
        ])->assertForbidden();

        $this->actingAs($admin, 'web')->get('/admin/content/article/entries/' . $entry->id . '/revisions')->assertForbidden();
        $this->actingAs($admin, 'web')->post('/admin/content/article/entries/' . $entry->id . '/revisions/' . $revision->id . '/rollback')->assertForbidden();

        $this->actingAs($admin, 'web')->get('/api/v1/admin/content/article/relations/picker?field_key=title')->assertForbidden();

        Sanctum::actingAs($admin, ['*']);

        $this->getJson('/api/v1/content/article')->assertForbidden();
        $this->getJson('/api/v1/content/article/' . $entry->slug)->assertForbidden();
    }
}
