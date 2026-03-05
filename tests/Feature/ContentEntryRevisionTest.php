<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Pagify\Content\Models\ContentEntry;
use Pagify\Content\Models\ContentEntryRevision;
use Pagify\Content\Models\ContentType;
use Pagify\Core\Models\Admin;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ContentEntryRevisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_and_update_generate_revisions_and_diff(): void
    {
        $admin = $this->makeAdminWithPermissions([
            'content.entry.create',
            'content.entry.update',
            'content.entry.viewAny',
            'content.entry.revision.view',
        ]);

        $contentType = $this->makeArticleType();

        $createResponse = $this->actingAs($admin, 'web')->post('/admin/content/article/entries', [
            'slug' => 'revision-entry',
            'status' => 'draft',
            'data' => [
                'title' => 'First title',
            ],
        ]);

        $createResponse->assertRedirect();

        $entry = ContentEntry::query()->where('slug', 'revision-entry')->firstOrFail();

        $this->actingAs($admin, 'web')->put('/admin/content/article/entries/' . $entry->id, [
            'slug' => 'revision-entry',
            'status' => 'published',
            'data' => [
                'title' => 'Second title',
            ],
        ])->assertRedirect();

        $revisions = ContentEntryRevision::query()
            ->where('content_entry_id', $entry->id)
            ->orderBy('revision_no')
            ->get();

        $this->assertCount(2, $revisions);
        $this->assertSame('created', $revisions[0]->action);
        $this->assertSame('updated', $revisions[1]->action);
        $this->assertTrue((bool) ($revisions[1]->diff_json['changed'] ?? false));

        $revisionsPage = $this->actingAs($admin, 'web')->get('/admin/content/article/entries/' . $entry->id . '/revisions');
        $revisionsPage->assertOk();
        $revisionsPage->assertInertia(fn (Assert $page) => $page
            ->component('Content/Entries/Revisions/Index')
            ->where('entry.slug', 'revision-entry')
            ->where('breadcrumbs.0.label_key', 'dashboard')
            ->where('breadcrumbs.3.label', 'revision-entry')
            ->where('breadcrumbs.4.label_key', 'view_revisions')
            ->has('diff.changes')
        );
    }

    public function test_rollback_creates_new_revision_head(): void
    {
        $admin = $this->makeAdminWithPermissions([
            'content.entry.create',
            'content.entry.update',
            'content.entry.viewAny',
            'content.entry.revision.view',
            'content.entry.revision.rollback',
        ]);

        $contentType = $this->makeArticleType();

        $this->actingAs($admin, 'web')->post('/admin/content/article/entries', [
            'slug' => 'rollback-entry',
            'status' => 'draft',
            'data' => [
                'title' => 'Version 1',
            ],
        ]);

        $entry = ContentEntry::query()->where('slug', 'rollback-entry')->firstOrFail();

        $this->actingAs($admin, 'web')->put('/admin/content/article/entries/' . $entry->id, [
            'slug' => 'rollback-entry',
            'status' => 'published',
            'data' => [
                'title' => 'Version 2',
            ],
        ]);

        $firstRevision = ContentEntryRevision::query()
            ->where('content_entry_id', $entry->id)
            ->where('revision_no', 1)
            ->firstOrFail();

        $rollbackResponse = $this->actingAs($admin, 'web')
            ->post('/admin/content/article/entries/' . $entry->id . '/revisions/' . $firstRevision->id . '/rollback');

        $rollbackResponse->assertRedirect();

        $entry->refresh();
        $this->assertSame('Version 1', $entry->data_json['title'] ?? null);
        $this->assertSame('draft', $entry->status);

        $latestRevision = ContentEntryRevision::query()
            ->where('content_entry_id', $entry->id)
            ->latest('revision_no')
            ->firstOrFail();

        $this->assertSame('rollback', $latestRevision->action);
        $this->assertSame($firstRevision->id, (int) ($latestRevision->metadata_json['rolled_back_from_revision_id'] ?? 0));
    }

    public function test_revision_operations_are_permission_protected(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $contentType = $this->makeArticleType();

        $entry = ContentEntry::query()->create([
            'content_type_id' => $contentType->id,
            'slug' => 'no-permission-entry',
            'status' => 'draft',
            'data_json' => ['title' => 'Title'],
        ]);

        $revision = ContentEntryRevision::query()->create([
            'content_entry_id' => $entry->id,
            'revision_no' => 1,
            'action' => 'created',
            'snapshot_json' => [
                'slug' => $entry->slug,
                'status' => $entry->status,
                'data' => $entry->data_json,
            ],
        ]);

        $this->actingAs($admin, 'web')
            ->get('/admin/content/article/entries/' . $entry->id . '/revisions')
            ->assertForbidden();

        $this->actingAs($admin, 'web')
            ->post('/admin/content/article/entries/' . $entry->id . '/revisions/' . $revision->id . '/rollback')
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
