<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Pagify\Content\Models\ContentEntry;
use Pagify\Content\Models\ContentRelation;
use Pagify\Content\Models\ContentType;
use Pagify\Core\Models\Admin;
use Pagify\Core\Models\Site;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ContentRelationEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_one_relation_sync_hydration_and_picker_api(): void
    {
        $admin = $this->makeAdminWithPermissions([
            'content.entry.create',
            'content.entry.update',
            'content.entry.viewAny',
            'content.relation.view',
        ]);

        $authorType = ContentType::query()->create([
            'name' => 'Author',
            'slug' => 'author',
            'is_active' => true,
            'schema_json' => ['version' => 1, 'fields' => []],
        ]);

        $authorType->fields()->create([
            'key' => 'name',
            'label' => 'Name',
            'field_type' => 'text',
            'sort_order' => 0,
            'is_required' => true,
            'is_localized' => false,
        ]);

        $articleType = ContentType::query()->create([
            'name' => 'Article',
            'slug' => 'article',
            'is_active' => true,
            'schema_json' => ['version' => 1, 'fields' => []],
        ]);

        $articleType->fields()->createMany([
            [
                'key' => 'title',
                'label' => 'Title',
                'field_type' => 'text',
                'sort_order' => 0,
                'is_required' => true,
                'is_localized' => false,
            ],
            [
                'key' => 'author_ref',
                'label' => 'Author',
                'field_type' => 'relation',
                'config_json' => [
                    'relation_type' => 'hasOne',
                    'target_content_type_slug' => 'author',
                ],
                'sort_order' => 1,
                'is_required' => false,
                'is_localized' => false,
            ],
        ]);

        $authorEntry = ContentEntry::query()->create([
            'content_type_id' => $authorType->id,
            'slug' => 'author-jane',
            'status' => 'published',
            'data_json' => ['name' => 'Jane'],
        ]);

        $createResponse = $this->actingAs($admin, 'web')->post('/admin/content/article/entries', [
            'slug' => 'article-one',
            'status' => 'draft',
            'data' => [
                'title' => 'Article one',
                'author_ref' => $authorEntry->id,
            ],
        ]);

        $createResponse->assertRedirect();

        $articleEntry = ContentEntry::query()->where('slug', 'article-one')->firstOrFail();

        $this->assertDatabaseHas('content_relations', [
            'source_entry_id' => $articleEntry->id,
            'target_entry_id' => $authorEntry->id,
            'field_key' => 'author_ref',
            'relation_type' => 'hasOne',
        ]);

        $this->actingAs($admin, 'web')
            ->get('/admin/content/article/entries/' . $articleEntry->id . '/edit')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Content/Entries/Edit')
                ->where('entry.slug', 'article-one')
                ->has('resolvedRelations.author_ref', 1)
                ->where('resolvedRelations.author_ref.0.target_slug', 'author-jane')
            );

        $this->actingAs($admin, 'web')
            ->get('/admin/content/article/entries')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Content/Entries/Index')
                ->has('entries.data', 1)
                ->where('entries.data.0.relation_slugs.0', 'author-jane')
            );

        $pickerResponse = $this->actingAs($admin, 'web')
            ->get('/api/v1/admin/content/article/relations/picker?field_key=author_ref&q=author-');

        $pickerResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.field_key', 'author_ref')
            ->assertJsonPath('data.relation_type', 'hasOne')
            ->assertJsonPath('data.options.0.slug', 'author-jane');
    }

    public function test_has_one_cycle_is_rejected(): void
    {
        $admin = $this->makeAdminWithPermissions([
            'content.entry.update',
            'content.entry.viewAny',
        ]);

        $nodeType = ContentType::query()->create([
            'name' => 'Node',
            'slug' => 'node',
            'is_active' => true,
            'schema_json' => ['version' => 1, 'fields' => []],
        ]);

        $nodeType->fields()->createMany([
            [
                'key' => 'title',
                'label' => 'Title',
                'field_type' => 'text',
                'sort_order' => 0,
                'is_required' => true,
                'is_localized' => false,
            ],
            [
                'key' => 'parent',
                'label' => 'Parent',
                'field_type' => 'relation',
                'config_json' => [
                    'relation_type' => 'hasOne',
                    'target_content_type_slug' => 'node',
                ],
                'sort_order' => 1,
                'is_required' => false,
                'is_localized' => false,
            ],
        ]);

        $entryA = ContentEntry::query()->create([
            'content_type_id' => $nodeType->id,
            'slug' => 'node-a',
            'status' => 'draft',
            'data_json' => ['title' => 'Node A'],
        ]);

        $entryB = ContentEntry::query()->create([
            'content_type_id' => $nodeType->id,
            'slug' => 'node-b',
            'status' => 'draft',
            'data_json' => ['title' => 'Node B'],
        ]);

        $this->actingAs($admin, 'web')
            ->put('/admin/content/node/entries/' . $entryA->id, [
                'slug' => 'node-a',
                'status' => 'draft',
                'data' => [
                    'title' => 'Node A',
                    'parent' => $entryB->id,
                ],
            ])
            ->assertRedirect();

        $cycleResponse = $this->actingAs($admin, 'web')
            ->from('/admin/content/node/entries/' . $entryB->id . '/edit')
            ->put('/admin/content/node/entries/' . $entryB->id, [
                'slug' => 'node-b',
                'status' => 'draft',
                'data' => [
                    'title' => 'Node B',
                    'parent' => $entryA->id,
                ],
            ]);

        $cycleResponse
            ->assertRedirect('/admin/content/node/entries/' . $entryB->id . '/edit')
            ->assertSessionHasErrors(['data.parent']);
    }

    public function test_cross_site_relation_is_rejected(): void
    {
        $siteA = Site::query()->create([
            'name' => 'Site A',
            'slug' => 'site-a',
            'domain' => 'a.local',
            'locale' => 'en',
            'is_active' => true,
        ]);

        $siteB = Site::query()->create([
            'name' => 'Site B',
            'slug' => 'site-b',
            'domain' => 'b.local',
            'locale' => 'en',
            'is_active' => true,
        ]);

        $admin = $this->makeAdminWithPermissions([
            'content.entry.update',
            'content.entry.viewAny',
        ]);

        $typeA = ContentType::withoutGlobalScopes()->create([
            'site_id' => $siteA->id,
            'name' => 'Article A',
            'slug' => 'article',
            'is_active' => true,
            'schema_json' => ['version' => 1, 'fields' => []],
        ]);

        $typeA->fields()->createMany([
            [
                'key' => 'title',
                'label' => 'Title',
                'field_type' => 'text',
                'sort_order' => 0,
                'is_required' => true,
                'is_localized' => false,
            ],
            [
                'key' => 'related',
                'label' => 'Related',
                'field_type' => 'relation',
                'config_json' => [
                    'relation_type' => 'hasMany',
                    'target_content_type_slug' => 'article',
                ],
                'sort_order' => 1,
                'is_required' => false,
                'is_localized' => false,
            ],
        ]);

        $typeB = ContentType::withoutGlobalScopes()->create([
            'site_id' => $siteB->id,
            'name' => 'Article B',
            'slug' => 'article',
            'is_active' => true,
            'schema_json' => ['version' => 1, 'fields' => []],
        ]);

        $sourceEntry = ContentEntry::withoutGlobalScopes()->create([
            'site_id' => $siteA->id,
            'content_type_id' => $typeA->id,
            'slug' => 'source-a',
            'status' => 'draft',
            'data_json' => ['title' => 'Source A'],
        ]);

        $targetEntry = ContentEntry::withoutGlobalScopes()->create([
            'site_id' => $siteB->id,
            'content_type_id' => $typeB->id,
            'slug' => 'target-b',
            'status' => 'draft',
            'data_json' => ['title' => 'Target B'],
        ]);

        $response = $this->actingAs($admin, 'web')
            ->withServerVariables(['HTTP_HOST' => 'a.local'])
            ->from('/admin/content/article/entries/' . $sourceEntry->id . '/edit')
            ->put('/admin/content/article/entries/' . $sourceEntry->id, [
                'slug' => 'source-a',
                'status' => 'draft',
                'data' => [
                    'title' => 'Source A',
                    'related' => [$targetEntry->id],
                ],
            ]);

        $response
            ->assertRedirect('/admin/content/article/entries/' . $sourceEntry->id . '/edit')
            ->assertSessionHasErrors(['data.related']);

        $this->assertDatabaseCount('content_relations', 0);
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
