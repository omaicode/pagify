<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\Sanctum;
use Pagify\Content\Models\ContentEntry;
use Pagify\Content\Models\ContentType;
use Pagify\Core\Models\Admin;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ContentAuthoringLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_authoring_lifecycle_type_entry_revision_publish_api(): void
    {
        $admin = $this->makeAdminWithPermissions([
            'content.type.create',
            'content.type.update',
            'content.entry.viewAny',
            'content.entry.create',
            'content.entry.update',
            'content.entry.publish',
            'content.entry.revision.view',
            'content.api.read.article',
        ]);

        $createTypeResponse = $this->actingAs($admin, 'web')->post('/admin/content/types', [
            'name' => 'Article',
            'slug' => 'article',
            'is_active' => 1,
            'fields' => [
                [
                    'key' => 'title',
                    'label' => 'Title',
                    'field_type' => 'text',
                    'sort_order' => 0,
                    'is_required' => 1,
                ],
                [
                    'key' => 'category',
                    'label' => 'Category',
                    'field_type' => 'select',
                    'config' => ['options' => ['news', 'blog']],
                    'sort_order' => 1,
                    'is_required' => 1,
                ],
            ],
        ]);

        $createTypeResponse->assertRedirect();

        $contentType = ContentType::query()->where('slug', 'article')->firstOrFail();

        $createEntryResponse = $this->actingAs($admin, 'web')->post('/admin/content/article/entries', [
            'slug' => 'article-lifecycle',
            'status' => 'draft',
            'data' => [
                'title' => 'Lifecycle title',
                'category' => 'news',
            ],
        ]);

        $createEntryResponse->assertRedirect();

        $entry = ContentEntry::query()->where('slug', 'article-lifecycle')->firstOrFail();

        $this->actingAs($admin, 'web')->put('/admin/content/article/entries/' . $entry->id, [
            'slug' => 'article-lifecycle',
            'status' => 'draft',
            'data' => [
                'title' => 'Lifecycle title updated',
                'category' => 'blog',
            ],
        ])->assertRedirect();

        $this->actingAs($admin, 'web')
            ->post('/admin/content/article/entries/' . $entry->id . '/publish')
            ->assertRedirect();

        $entry->refresh();
        $this->assertSame('published', $entry->status);

        $revisionsResponse = $this->actingAs($admin, 'web')
            ->get('/admin/content/article/entries/' . $entry->id . '/revisions');

        $revisionsResponse->assertOk();
        $revisionsResponse->assertInertia(fn (Assert $page) => $page
            ->component('Content/Entries/Revisions/Index')
            ->where('entry.slug', 'article-lifecycle')
        );

        $this->assertGreaterThanOrEqual(3, $entry->revisions()->count());

        Sanctum::actingAs($admin, ['*']);

        $this->getJson('/api/v1/content/article?status=published')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.content_type.id', $contentType->id)
            ->assertJsonPath('data.0.slug', 'article-lifecycle');

        $this->getJson('/api/v1/content/article/article-lifecycle')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'article-lifecycle')
            ->assertJsonPath('data.data.category', 'blog');
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
