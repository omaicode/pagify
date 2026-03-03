<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\Models\ContentType;
use Modules\Core\Models\Admin;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ContentTypeCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_permission_can_create_content_type(): void
    {
        $admin = $this->makeAdminWithPermissions([
            'content.type.create',
            'content.type.viewAny',
            'content.type.update',
        ]);

        $response = $this->actingAs($admin, 'web')->post('/admin/content/types', [
            'name' => 'Article',
            'slug' => 'article',
            'description' => 'Article content type',
            'is_active' => true,
            'fields' => [
                [
                    'key' => 'title',
                    'label' => 'Title',
                    'field_type' => 'text',
                    'sort_order' => 0,
                    'is_required' => true,
                ],
                [
                    'key' => 'category',
                    'label' => 'Category',
                    'field_type' => 'select',
                    'config' => ['options' => ['news', 'blog']],
                    'sort_order' => 1,
                ],
                [
                    'key' => 'featured_entry',
                    'label' => 'Featured entry',
                    'field_type' => 'relation',
                    'config' => [
                        'relation_type' => 'hasOne',
                        'target_type_slug' => 'article',
                    ],
                    'sort_order' => 2,
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('content_types', [
            'slug' => 'article',
            'name' => 'Article',
        ]);

        $contentType = ContentType::query()->where('slug', 'article')->first();

        $this->assertNotNull($contentType);
        $this->assertCount(3, $contentType->fields);
    }

    public function test_admin_with_permission_can_update_and_delete_content_type(): void
    {
        $admin = $this->makeAdminWithPermissions([
            'content.type.create',
            'content.type.viewAny',
            'content.type.update',
            'content.type.delete',
        ]);

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

        $updateResponse = $this->actingAs($admin, 'web')->put('/admin/content/types/' . $contentType->id, [
            'name' => 'Article Updated',
            'slug' => 'article-updated',
            'description' => 'Updated description',
            'is_active' => true,
            'fields' => [
                [
                    'key' => 'headline',
                    'label' => 'Headline',
                    'field_type' => 'text',
                    'sort_order' => 0,
                    'is_required' => true,
                ],
            ],
        ]);

        $updateResponse->assertRedirect();

        $this->assertDatabaseHas('content_types', [
            'id' => $contentType->id,
            'slug' => 'article-updated',
        ]);

        $deleteResponse = $this->actingAs($admin, 'web')->delete('/admin/content/types/' . $contentType->id);

        $deleteResponse->assertRedirect('/admin/content/types');

        $this->assertDatabaseMissing('content_types', [
            'id' => $contentType->id,
        ]);
    }

    public function test_admin_without_permission_gets_forbidden_for_create_page(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin, 'web')->get('/admin/content/types/create');

        $response->assertForbidden();
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
