<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Content\Models\ContentEntry;
use Modules\Content\Models\ContentType;
use Modules\Core\Models\Admin;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ContentApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_list_supports_filter_sort_pagination_and_resource_shape(): void
    {
        $admin = $this->makeAdminWithPermissions([
            'content.api.read.article',
        ]);

        Sanctum::actingAs($admin, ['*']);

        $contentType = $this->makeArticleType();

        ContentEntry::query()->create([
            'content_type_id' => $contentType->id,
            'slug' => 'article-b',
            'status' => 'published',
            'data_json' => ['title' => 'Title B', 'category' => 'news'],
        ]);

        ContentEntry::query()->create([
            'content_type_id' => $contentType->id,
            'slug' => 'article-a',
            'status' => 'published',
            'data_json' => ['title' => 'Title A', 'category' => 'news'],
        ]);

        ContentEntry::query()->create([
            'content_type_id' => $contentType->id,
            'slug' => 'article-c',
            'status' => 'draft',
            'data_json' => ['title' => 'Title C', 'category' => 'blog'],
        ]);

        $response = $this->getJson('/api/v1/content/article?status=published&filter[category]=news&sort_by=slug&sort_dir=asc&per_page=1&page=1');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.pagination.current_page', 1)
            ->assertJsonPath('meta.pagination.per_page', 1)
            ->assertJsonPath('meta.pagination.total', 2)
            ->assertJsonPath('meta.sort.by', 'slug')
            ->assertJsonPath('meta.sort.dir', 'asc')
            ->assertJsonPath('data.0.slug', 'article-a')
            ->assertJsonPath('data.0.content_type.slug', 'article')
            ->assertJsonPath('data.0.data.category', 'news');
    }

    public function test_api_show_returns_entry_by_slug_with_relations_payload(): void
    {
        $admin = $this->makeAdminWithPermissions([
            'content.api.read',
        ]);

        Sanctum::actingAs($admin, ['*']);

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

        $articleType = $this->makeArticleType(true);

        $authorEntry = ContentEntry::query()->create([
            'content_type_id' => $authorType->id,
            'slug' => 'author-john',
            'status' => 'published',
            'data_json' => ['name' => 'John'],
        ]);

        $articleEntry = ContentEntry::query()->create([
            'content_type_id' => $articleType->id,
            'slug' => 'article-with-author',
            'status' => 'published',
            'data_json' => ['title' => 'Article', 'category' => 'news', 'author_ref' => $authorEntry->id],
        ]);

        \Modules\Content\Models\ContentRelation::query()->create([
            'source_entry_id' => $articleEntry->id,
            'target_entry_id' => $authorEntry->id,
            'field_key' => 'author_ref',
            'relation_type' => 'hasOne',
            'position' => 0,
        ]);

        $response = $this->getJson('/api/v1/content/article/article-with-author');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'article-with-author')
            ->assertJsonPath('data.relations.author_ref.0.target_slug', 'author-john');
    }

    public function test_api_access_requires_permission_per_content_type(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        Sanctum::actingAs($admin, ['*']);

        $this->makeArticleType();

        $this->getJson('/api/v1/content/article')
            ->assertForbidden();
    }

    private function makeArticleType(bool $withRelation = false): ContentType
    {
        $contentType = ContentType::query()->create([
            'name' => 'Article',
            'slug' => 'article',
            'is_active' => true,
            'schema_json' => ['version' => 1, 'fields' => []],
        ]);

        $fields = [
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
        ];

        if ($withRelation) {
            $fields[] = [
                'key' => 'author_ref',
                'label' => 'Author',
                'field_type' => 'relation',
                'config_json' => [
                    'relation_type' => 'hasOne',
                    'target_content_type_slug' => 'author',
                ],
                'sort_order' => 2,
                'is_required' => false,
                'is_localized' => false,
            ];
        }

        $contentType->fields()->createMany($fields);

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
