<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Pagify\Core\Models\Admin;
use Pagify\Updater\Jobs\RunUpdaterExecutionJob;
use Pagify\Updater\Models\UpdaterExecution;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UpdaterApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_updater_api_redirects_guest_to_login(): void
    {
        $this->get('/api/v1/admin/updater/executions')->assertRedirect('/admin/login');
        $this->get('/api/v1/admin/updater/executions/1')->assertRedirect('/admin/login');

        $this->postJson('/api/v1/admin/updater/executions/dry-run', [
            'target_type' => 'all',
        ])->assertUnauthorized();

        $this->postJson('/api/v1/admin/updater/executions/all')->assertUnauthorized();
        $this->postJson('/api/v1/admin/updater/executions/module/core')->assertUnauthorized();
    }

    public function test_updater_api_returns_403_without_permission(): void
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $execution = UpdaterExecution::query()->create([
            'requested_by_admin_id' => $admin->id,
            'target_type' => 'module',
            'target_value' => 'core',
            'status' => 'failed',
            'meta' => ['requested_at' => now()->toIso8601String()],
        ]);

        $this->actingAs($admin, 'web')->get('/api/v1/admin/updater/executions')->assertForbidden();
        $this->actingAs($admin, 'web')->get('/api/v1/admin/updater/executions/' . $execution->id)->assertForbidden();

        $this->actingAs($admin, 'web')->postJson('/api/v1/admin/updater/executions/dry-run', [
            'target_type' => 'all',
        ])->assertForbidden();

        $this->actingAs($admin, 'web')->postJson('/api/v1/admin/updater/executions/all')->assertForbidden();

        $this->actingAs($admin, 'web')->postJson('/api/v1/admin/updater/executions/module/core')->assertForbidden();

        $this->actingAs($admin, 'web')
            ->postJson('/api/v1/admin/updater/executions/' . $execution->id . '/rollback')
            ->assertForbidden();
    }

    public function test_dry_run_returns_plan_for_all_modules(): void
    {
        $admin = $this->makeAdminWithUpdaterPermission();

        $response = $this->actingAs($admin, 'web')->postJson('/api/v1/admin/updater/executions/dry-run', [
            'target_type' => 'all',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.target_type', 'all')
            ->assertJsonPath('data.summary.total_modules', count($response->json('data.items')))
            ->assertJsonPath('data.summary.resolved_packages', count($response->json('data.items')));

        $items = $response->json('data.items');

        $this->assertIsArray($items);
        $this->assertNotEmpty($items);

        $resolvedPackages = collect($items)
            ->pluck('package_name')
            ->all();

        $this->assertContains('omaicode/pagify-core', $resolvedPackages);
    }

    public function test_dry_run_returns_validation_error_for_invalid_target_type(): void
    {
        $admin = $this->makeAdminWithUpdaterPermission();

        $this->actingAs($admin, 'web')
            ->postJson('/api/v1/admin/updater/executions/dry-run', [
                'target_type' => 'plugin',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'INVALID_TARGET_TYPE');
    }

    public function test_dry_run_returns_not_found_for_unknown_module_target(): void
    {
        $admin = $this->makeAdminWithUpdaterPermission();

        $this->actingAs($admin, 'web')
            ->postJson('/api/v1/admin/updater/executions/dry-run', [
                'target_type' => 'module',
                'target_value' => 'unknown-module',
            ])
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'MODULE_NOT_FOUND');
    }

    public function test_update_module_enqueues_execution_job(): void
    {
        Bus::fake();

        $admin = $this->makeAdminWithUpdaterPermission();

        $response = $this->actingAs($admin, 'web')
            ->postJson('/api/v1/admin/updater/executions/module/core');

        $response
            ->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.target_type', 'module')
            ->assertJsonPath('data.target_value', 'core');

        $executionId = (int) $response->json('data.execution_id');

        $this->assertDatabaseHas('updater_executions', [
            'id' => $executionId,
            'target_type' => 'module',
            'target_value' => 'core',
            'status' => 'queued',
        ]);

        Bus::assertDispatched(RunUpdaterExecutionJob::class, function (RunUpdaterExecutionJob $job) use ($executionId): bool {
            return $job->executionId === $executionId;
        });
    }

    public function test_update_all_enqueues_execution_job(): void
    {
        Bus::fake();

        $admin = $this->makeAdminWithUpdaterPermission();

        $response = $this->actingAs($admin, 'web')
            ->postJson('/api/v1/admin/updater/executions/all');

        $response
            ->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.target_type', 'all')
            ->assertJsonPath('data.target_value', null);

        $executionId = (int) $response->json('data.execution_id');

        $this->assertDatabaseHas('updater_executions', [
            'id' => $executionId,
            'target_type' => 'all',
            'status' => 'queued',
        ]);

        Bus::assertDispatched(RunUpdaterExecutionJob::class, function (RunUpdaterExecutionJob $job) use ($executionId): bool {
            return $job->executionId === $executionId;
        });
    }

    public function test_rollback_returns_failure_when_snapshot_missing(): void
    {
        $admin = $this->makeAdminWithUpdaterPermission();

        $execution = UpdaterExecution::query()->create([
            'requested_by_admin_id' => $admin->id,
            'target_type' => 'module',
            'target_value' => 'core',
            'status' => 'failed',
            'snapshot_path' => 'updater/snapshots/non-existent.lock',
            'meta' => ['requested_at' => now()->toIso8601String()],
        ]);

        $this->actingAs($admin, 'web')
            ->postJson('/api/v1/admin/updater/executions/' . $execution->id . '/rollback')
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'ROLLBACK_FAILED');
    }

    public function test_execution_detail_returns_compact_log_output_for_polling(): void
    {
        $admin = $this->makeAdminWithUpdaterPermission();

        $execution = UpdaterExecution::query()->create([
            'requested_by_admin_id' => $admin->id,
            'target_type' => 'module',
            'target_value' => 'core',
            'status' => 'failed',
            'meta' => ['requested_at' => now()->toIso8601String()],
            'result' => [
                'exit_code' => 1,
                'output' => str_repeat('A', 2500),
                'error_output' => str_repeat('B', 50),
            ],
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);

        $execution->items()->create([
            'module_slug' => 'core',
            'package_name' => 'omaicode/pagify-core',
            'status' => 'failed',
            'from_version' => '1.0.0',
            'to_version' => '1.0.0',
            'error_message' => 'Composer conflict',
        ]);

        $response = $this->actingAs($admin, 'web')
            ->get('/api/v1/admin/updater/executions/' . $execution->id);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $execution->id)
            ->assertJsonPath('data.status', 'failed')
            ->assertJsonPath('data.log.exit_code', 1)
            ->assertJsonPath('data.log.output_truncated', true)
            ->assertJsonPath('data.log.error_output_truncated', false)
            ->assertJsonPath('data.items.0.module_slug', 'core');

        $this->assertSame(2000, mb_strlen((string) $response->json('data.log.output_excerpt')));
        $this->assertSame(50, mb_strlen((string) $response->json('data.log.error_output_excerpt')));
    }

    private function makeAdminWithUpdaterPermission(): Admin
    {
        /** @var Admin $admin */
        $admin = Admin::factory()->create();

        $permission = Permission::query()->firstOrCreate([
            'name' => 'core.updater.manage',
            'guard_name' => 'web',
        ]);

        $admin->givePermissionTo($permission);

        return $admin;
    }
}
