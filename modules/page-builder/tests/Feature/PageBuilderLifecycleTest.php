<?php

namespace Pagify\PageBuilder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Pagify\Core\Models\Admin;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Models\PageRevision;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PageBuilderLifecycleTest extends TestCase
{
	use RefreshDatabase;

	public function test_create_update_publish_generate_revisions_and_snapshot(): void
	{
		$admin = $this->makeAdminWithPermissions([
			'page-builder.page.viewAny',
			'page-builder.page.create',
			'page-builder.page.update',
			'page-builder.page.publish',
			'page-builder.page.revision.view',
		]);

		$this->actingAs($admin, 'web')->post('/admin/page-builder/pages', [
			'title' => 'Landing Page',
			'slug' => 'landing-page',
			'status' => 'draft',
			'layout' => [
				'sections' => [
					[
						'id' => 'hero',
						'blocks' => [
							['type' => 'heading', 'props' => ['text' => 'Welcome']],
						],
					],
				],
			],
			'seo_meta' => [
				'title' => 'Landing',
				'description' => 'Landing description',
			],
		])->assertRedirect();

		$page = Page::query()->where('slug', 'landing-page')->firstOrFail();
		$this->assertSame('draft', $page->status);

		$this->actingAs($admin, 'web')->put('/admin/page-builder/pages/' . $page->id, [
			'title' => 'Landing Page Updated',
			'slug' => 'landing-page',
			'status' => 'draft',
			'layout' => [
				'sections' => [
					[
						'id' => 'hero',
						'blocks' => [
							['type' => 'heading', 'props' => ['text' => 'Updated headline']],
						],
					],
				],
			],
			'seo_meta' => [
				'title' => 'Landing SEO',
				'description' => 'Updated description',
				'canonical_url' => 'https://example.test/pages/landing-page',
			],
		])->assertRedirect();

		$this->actingAs($admin, 'web')->post('/admin/page-builder/pages/' . $page->id . '/publish')->assertRedirect();

		$page->refresh();
		$this->assertSame('published', $page->status);
		$this->assertNotNull($page->snapshot_generated_at);
		$this->assertStringContainsString('<title>Landing SEO</title>', (string) $page->snapshot_html);

		$revisions = PageRevision::query()
			->where('page_id', $page->id)
			->orderBy('revision_no')
			->pluck('action')
			->all();

		$this->assertSame(['created', 'updated', 'published'], $revisions);
	}

	public function test_rollback_revision_restores_previous_snapshot(): void
	{
		$admin = $this->makeAdminWithPermissions([
			'page-builder.page.viewAny',
			'page-builder.page.create',
			'page-builder.page.update',
			'page-builder.page.revision.view',
			'page-builder.page.revision.rollback',
		]);

		$this->actingAs($admin, 'web')->post('/admin/page-builder/pages', [
			'title' => 'Docs',
			'slug' => 'docs-page',
			'status' => 'draft',
			'layout' => ['sections' => []],
		])->assertRedirect();

		$page = Page::query()->where('slug', 'docs-page')->firstOrFail();

		$this->actingAs($admin, 'web')->put('/admin/page-builder/pages/' . $page->id, [
			'title' => 'Docs Updated',
			'slug' => 'docs-page',
			'status' => 'draft',
			'layout' => ['sections' => [['id' => 'intro', 'blocks' => []]]],
		])->assertRedirect();

		$firstRevision = PageRevision::query()
			->where('page_id', $page->id)
			->where('revision_no', 1)
			->firstOrFail();

		$this->actingAs($admin, 'web')
			->post('/admin/page-builder/pages/' . $page->id . '/revisions/' . $firstRevision->id . '/rollback')
			->assertRedirect();

		$page->refresh();
		$this->assertSame('Docs', $page->title);
	}

	public function test_registry_api_returns_blocks_and_breakpoints(): void
	{
		$admin = $this->makeAdminWithPermissions([
			'page-builder.page.viewAny',
		]);

		$response = $this->actingAs($admin, 'web')->get('/api/v1/admin/page-builder/registry');

		$response
			->assertOk()
			->assertJsonPath('success', true)
			->assertJsonStructure([
				'success',
				'data' => ['blocks', 'breakpoints'],
			]);
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
