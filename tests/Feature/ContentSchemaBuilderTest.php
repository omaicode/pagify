<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Content\Jobs\QueueSchemaMigrationPlanJob;
use Modules\Content\Models\ContentSchemaMigrationPlan;
use Modules\Content\Models\ContentType;
use Modules\Core\Models\Admin;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ContentSchemaBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_page_renders_and_save_queues_plan_job(): void
    {
        Bus::fake();

        $admin = $this->makeAdminWithPermissions([
            'content.type.update',
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

        $this->actingAs($admin, 'web')
            ->get('/admin/content/types/' . $contentType->id . '/builder')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Content/Types/Builder/Edit')
                ->where('contentType.slug', 'article')
                ->has('initialFields', 1)
            );

        $response = $this->actingAs($admin, 'web')
            ->put('/admin/content/types/' . $contentType->id . '/builder', [
                'fields' => [
                    [
                        'key' => 'title',
                        'label' => 'Title',
                        'field_type' => 'text',
                        'sort_order' => 0,
                        'is_required' => 1,
                        'is_localized' => 0,
                        'config' => '{}',
                        'validation' => '{}',
                        'conditional' => '{}',
                    ],
                    [
                        'key' => 'summary',
                        'label' => 'Summary',
                        'field_type' => 'richtext',
                        'sort_order' => 1,
                        'is_required' => 0,
                        'is_localized' => 0,
                        'config' => '{}',
                        'validation' => '{}',
                        'conditional' => '{}',
                    ],
                ],
            ]);

        $response->assertRedirect('/admin/content/types/' . $contentType->id . '/builder/status');

        $plan = ContentSchemaMigrationPlan::query()->where('content_type_id', $contentType->id)->latest('id')->first();

        $this->assertNotNull($plan);
        $this->assertSame('queued', $plan->status);

        Bus::assertDispatched(QueueSchemaMigrationPlanJob::class, function (QueueSchemaMigrationPlanJob $job) use ($plan): bool {
            return $job->planId === $plan->id;
        });

        $contentType->refresh();
        $fields = (array) ($contentType->schema_json['fields'] ?? []);

        $this->assertCount(2, $fields);
        $this->assertSame('summary', $fields[1]['key'] ?? null);
    }

    public function test_status_page_lists_generated_plan_after_job_handle(): void
    {
        $admin = $this->makeAdminWithPermissions([
            'content.type.update',
        ]);

        $contentType = ContentType::query()->create([
            'name' => 'Article',
            'slug' => 'article',
            'is_active' => true,
            'schema_json' => ['version' => 1, 'fields' => []],
        ]);

        $plan = ContentSchemaMigrationPlan::query()->create([
            'content_type_id' => $contentType->id,
            'status' => 'planned',
            'schema_before_json' => [],
            'schema_after_json' => [],
            'plan_json' => [
                'summary' => [
                    'additions' => 1,
                    'removals' => 0,
                    'updates' => 0,
                ],
            ],
        ]);

        $this->actingAs($admin, 'web')
            ->get('/admin/content/types/' . $contentType->id . '/builder/status')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Content/Types/Builder/Status')
                ->has('plans.data', 1)
                ->where('plans.data.0.id', $plan->id)
                ->where('plans.data.0.summary.additions', 1)
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
