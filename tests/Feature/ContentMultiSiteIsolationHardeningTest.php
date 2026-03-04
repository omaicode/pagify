<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Content\Models\ContentEntry;
use Modules\Content\Models\ContentEntryRevision;
use Modules\Content\Models\ContentRelation;
use Modules\Content\Models\ContentSchemaMigrationPlan;
use Modules\Content\Models\ContentType;
use Modules\Core\Models\Admin;
use Modules\Core\Models\Site;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ContentMultiSiteIsolationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_site_isolation_across_type_entry_relation_revision_and_schema_plan(): void
    {
        $siteA = Site::factory()->create(['domain' => 'a.local', 'slug' => 'site-a']);
        $siteB = Site::factory()->create(['domain' => 'b.local', 'slug' => 'site-b']);

        $admin = $this->makeAdminWithPermissions([
            'content.type.viewAny',
            'content.type.update',
            'content.entry.viewAny',
            'content.entry.revision.view',
            'content.relation.view',
            'content.api.read.article',
        ]);

        $typeA = ContentType::withoutGlobalScopes()->create([
            'site_id' => $siteA->id,
            'name' => 'Article A',
            'slug' => 'article',
            'description' => null,
            'is_active' => true,
            'schema_json' => ['version' => 1, 'fields' => []],
        ]);

        $typeB = ContentType::withoutGlobalScopes()->create([
            'site_id' => $siteB->id,
            'name' => 'Article B',
            'slug' => 'article',
            'description' => null,
            'is_active' => true,
            'schema_json' => ['version' => 1, 'fields' => []],
        ]);

        $typeA->fields()->create([
            'key' => 'title',
            'label' => 'Title',
            'field_type' => 'text',
            'sort_order' => 0,
            'is_required' => true,
            'is_localized' => false,
        ]);

        $typeB->fields()->create([
            'key' => 'title',
            'label' => 'Title',
            'field_type' => 'text',
            'sort_order' => 0,
            'is_required' => true,
            'is_localized' => false,
        ]);

        $entryA = ContentEntry::withoutGlobalScopes()->create([
            'site_id' => $siteA->id,
            'content_type_id' => $typeA->id,
            'slug' => 'entry-a',
            'status' => 'draft',
            'data_json' => ['title' => 'A'],
            'schedule_metadata_json' => [],
        ]);

        $entryB = ContentEntry::withoutGlobalScopes()->create([
            'site_id' => $siteB->id,
            'content_type_id' => $typeB->id,
            'slug' => 'entry-b',
            'status' => 'draft',
            'data_json' => ['title' => 'B'],
            'schedule_metadata_json' => [],
        ]);

        ContentEntryRevision::withoutGlobalScopes()->create([
            'content_entry_id' => $entryA->id,
            'revision_no' => 1,
            'action' => 'created',
            'snapshot_json' => ['slug' => 'entry-a', 'status' => 'draft', 'data' => ['title' => 'A']],
            'diff_json' => null,
            'metadata_json' => [],
        ]);

        ContentEntryRevision::withoutGlobalScopes()->create([
            'content_entry_id' => $entryB->id,
            'revision_no' => 1,
            'action' => 'created',
            'snapshot_json' => ['slug' => 'entry-b', 'status' => 'draft', 'data' => ['title' => 'B']],
            'diff_json' => null,
            'metadata_json' => [],
        ]);

        ContentRelation::withoutGlobalScopes()->create([
            'site_id' => $siteA->id,
            'source_entry_id' => $entryA->id,
            'target_entry_id' => $entryA->id,
            'field_key' => 'self',
            'relation_type' => 'hasOne',
            'position' => 0,
            'metadata_json' => [],
        ]);

        ContentSchemaMigrationPlan::withoutGlobalScopes()->create([
            'site_id' => $siteA->id,
            'content_type_id' => $typeA->id,
            'requested_by_admin_id' => $admin->id,
            'status' => 'planned',
            'schema_before_json' => [],
            'schema_after_json' => [],
            'plan_json' => ['summary' => ['additions' => 1, 'removals' => 0, 'updates' => 0]],
        ]);

        ContentSchemaMigrationPlan::withoutGlobalScopes()->create([
            'site_id' => $siteB->id,
            'content_type_id' => $typeB->id,
            'requested_by_admin_id' => $admin->id,
            'status' => 'planned',
            'schema_before_json' => [],
            'schema_after_json' => [],
            'plan_json' => ['summary' => ['additions' => 1, 'removals' => 0, 'updates' => 0]],
        ]);

        $this->actingAs($admin, 'web')
            ->withServerVariables(['HTTP_HOST' => 'a.local'])
            ->get('/admin/content/types')
            ->assertOk();

        $this->actingAs($admin, 'web')
            ->withServerVariables(['HTTP_HOST' => 'a.local'])
            ->get('/admin/content/article/entries')
            ->assertOk()
            ->assertSee('entry-a')
            ->assertDontSee('entry-b');

        $this->actingAs($admin, 'web')
            ->withServerVariables(['HTTP_HOST' => 'a.local'])
            ->get('/admin/content/article/entries/' . $entryB->id . '/revisions')
            ->assertNotFound();

        $this->actingAs($admin, 'web')
            ->withServerVariables(['HTTP_HOST' => 'a.local'])
            ->get('/admin/content/types/' . $typeA->id . '/builder/status')
            ->assertOk()
            ->assertSee('Schema migration plans')
            ->assertDontSee('Article B');

        Sanctum::actingAs($admin, ['*']);

        $this->withServerVariables(['HTTP_HOST' => 'a.local'])
            ->getJson('/api/v1/content/article')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonMissing(['slug' => 'entry-b']);

        $this->actingAs($admin, 'web')
            ->withServerVariables(['HTTP_HOST' => 'a.local'])
            ->get('/api/v1/admin/content/article/relations/picker?field_key=self')
            ->assertStatus(422);
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
