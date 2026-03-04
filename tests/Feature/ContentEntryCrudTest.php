<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Content\Models\ContentEntry;
use Modules\Content\Models\ContentType;
use Modules\Core\Models\Admin;
use Modules\Core\Models\AuditLog;
use Modules\Core\Models\Site;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ContentEntryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_delete_entry_from_schema(): void
    {
        $admin = $this->makeAdminWithPermissions([
            'content.entry.viewAny',
            'content.entry.create',
            'content.entry.update',
            'content.entry.delete',
        ]);

        $contentType = ContentType::query()->create([
            'name' => 'Article',
            'slug' => 'article',
            'is_active' => true,
            'schema_json' => ['version' => 1, 'fields' => []],
        ]);

        $contentType->fields()->createMany([
            [
                'key' => 'title',
                'label' => 'Title',
                'field_type' => 'text',
                'sort_order' => 0,
                'is_required' => true,
                'is_localized' => false,
            ],
            [
                'key' => 'category',
                'label' => 'Category',
                'field_type' => 'select',
                'config_json' => ['options' => ['news', 'blog']],
                'sort_order' => 1,
                'is_required' => true,
                'is_localized' => false,
            ],
        ]);

        $createResponse = $this->actingAs($admin, 'web')->post('/admin/content/article/entries', [
            'slug' => 'entry-one',
            'status' => 'draft',
            'data' => [
                'title' => 'Entry One',
                'category' => 'news',
            ],
        ]);

        $createResponse->assertRedirect();

        $entry = ContentEntry::query()->where('slug', 'entry-one')->first();
        $this->assertNotNull($entry);
        $this->assertSame('Entry One', $entry->data_json['title'] ?? null);

        $updateResponse = $this->actingAs($admin, 'web')->put('/admin/content/article/entries/' . $entry->id, [
            'slug' => 'entry-one-updated',
            'status' => 'published',
            'data' => [
                'title' => 'Entry Updated',
                'category' => 'blog',
            ],
        ]);

        $updateResponse->assertRedirect();

        $entry->refresh();
        $this->assertSame('entry-one-updated', $entry->slug);
        $this->assertSame('published', $entry->status);
        $this->assertSame('blog', $entry->data_json['category'] ?? null);

        $deleteResponse = $this->actingAs($admin, 'web')->delete('/admin/content/article/entries/' . $entry->id);
        $deleteResponse->assertRedirect('/admin/content/article/entries');

        $this->assertDatabaseMissing('content_entries', [
            'id' => $entry->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'content.entry.created',
            'entity_type' => ContentEntry::class,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'content.entry.updated',
            'entity_type' => ContentEntry::class,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'content.entry.deleted',
            'entity_type' => ContentEntry::class,
        ]);

        $this->assertGreaterThanOrEqual(3, AuditLog::query()->where('entity_type', ContentEntry::class)->count());
    }

    public function test_entry_validation_fails_when_select_value_is_invalid(): void
    {
        $admin = $this->makeAdminWithPermissions([
            'content.entry.create',
            'content.entry.viewAny',
        ]);

        $contentType = ContentType::query()->create([
            'name' => 'Article',
            'slug' => 'article',
            'is_active' => true,
            'schema_json' => ['version' => 1, 'fields' => []],
        ]);

        $contentType->fields()->create([
            'key' => 'category',
            'label' => 'Category',
            'field_type' => 'select',
            'config_json' => ['options' => ['news', 'blog']],
            'sort_order' => 0,
            'is_required' => true,
            'is_localized' => false,
        ]);

        $response = $this->actingAs($admin, 'web')->from('/admin/content/article/entries/create')->post('/admin/content/article/entries', [
            'slug' => 'invalid-entry',
            'status' => 'draft',
            'data' => [
                'category' => 'invalid-option',
            ],
        ]);

        $response
            ->assertRedirect('/admin/content/article/entries/create')
            ->assertSessionHasErrors(['data.category']);
    }

    public function test_site_isolation_applies_for_entry_listing(): void
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
            'content.entry.viewAny',
        ]);

        $typeA = ContentType::withoutGlobalScopes()->create([
            'site_id' => $siteA->id,
            'name' => 'Article A',
            'slug' => 'article',
            'is_active' => true,
            'schema_json' => ['version' => 1, 'fields' => []],
        ]);

        $typeB = ContentType::withoutGlobalScopes()->create([
            'site_id' => $siteB->id,
            'name' => 'Article B',
            'slug' => 'article',
            'is_active' => true,
            'schema_json' => ['version' => 1, 'fields' => []],
        ]);

        ContentEntry::withoutGlobalScopes()->create([
            'site_id' => $siteA->id,
            'content_type_id' => $typeA->id,
            'slug' => 'entry-a',
            'status' => 'draft',
            'data_json' => ['title' => 'A'],
        ]);

        ContentEntry::withoutGlobalScopes()->create([
            'site_id' => $siteB->id,
            'content_type_id' => $typeB->id,
            'slug' => 'entry-b',
            'status' => 'draft',
            'data_json' => ['title' => 'B'],
        ]);

        $response = $this->actingAs($admin, 'web')
            ->withServerVariables(['HTTP_HOST' => 'a.local'])
            ->get('/admin/content/article/entries');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Content/Entries/Index')
            ->has('entries.data', 1)
            ->where('entries.data.0.slug', 'entry-a')
        );
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
