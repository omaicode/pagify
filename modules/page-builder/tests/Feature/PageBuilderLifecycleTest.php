<?php

namespace Pagify\PageBuilder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Pagify\Core\Models\Admin;
use Pagify\Media\Models\MediaAsset;
use Pagify\Media\Models\MediaUsage;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Services\EditorAccessTokenService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PageBuilderLifecycleTest extends TestCase
{
	use RefreshDatabase;

	public function test_create_update_publish_page_lifecycle(): void
	{
		$admin = $this->makeAdminWithPermissions([
			'page-builder.page.viewAny',
			'page-builder.page.create',
			'page-builder.page.update',
			'page-builder.page.publish',
		]);

		$this->actingAs($admin, 'web')->post('/admin/page-builder/pages', [
			'title' => 'Landing Page',
			'slug' => 'landing-page',
			'status' => 'draft',
			'layout' => [
				'type' => 'webstudio',
				'webstudio' => [
					'html' => '<main data-pbx-content-slot="true"><section class="pbx-section"><h2 class="pbx-subheading">Welcome</h2></section></main>',
					'css' => '.pbx-subheading{font-weight:600;}',
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
				'type' => 'webstudio',
				'webstudio' => [
					'html' => '<main data-pbx-content-slot="true"><section class="pbx-section"><h2 class="pbx-subheading">Updated headline</h2></section></main>',
					'css' => '.pbx-subheading{font-weight:700;}',
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
		$this->assertNotNull($page->published_at);
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

	public function test_create_and_publish_page_with_webstudio_layout_payload(): void
	{
		$admin = $this->makeAdminWithPermissions([
			'page-builder.page.viewAny',
			'page-builder.page.create',
			'page-builder.page.publish',
		]);

		$this->actingAs($admin, 'web')->post('/admin/page-builder/pages', [
			'title' => 'Webstudio Landing',
			'slug' => 'webstudio-landing',
			'status' => 'published',
			'layout' => [
				'type' => 'webstudio',
				'webstudio' => [
					'html' => '<main data-pbx-content-slot="true"><section class="pbx-section"><h2 class="pbx-subheading">Webstudio content</h2></section></main>',
					'css' => '.pbx-subheading{color:#0f172a;}',
					'updated_at' => now()->toIso8601String(),
				],
			],
		])->assertRedirect();

		$page = Page::query()->where('slug', 'webstudio-landing')->firstOrFail();
		$this->assertSame('published', $page->status);
		$this->assertNotNull($page->published_at);
		$this->assertStringContainsString('Webstudio content', (string) data_get($page->layout_json, 'webstudio.html', ''));
	}

	public function test_editor_access_token_endpoint_returns_token_for_create_context(): void
	{
		$admin = $this->makeAdminWithPermissions([
			'page-builder.page.create',
		]);

		$response = $this->actingAs($admin, 'web')
			->postJson('/api/v1/admin/page-builder/editor/access-token', []);

		$response
			->assertOk()
			->assertJsonPath('success', true)
			->assertJsonStructure([
				'success',
				'data' => ['access_token', 'expires_at'],
			]);
	}

	public function test_editor_access_token_endpoint_requires_update_permission_when_page_is_provided(): void
	{
		$creator = $this->makeAdminWithPermissions([
			'page-builder.page.create',
		]);

		$page = Page::query()->create([
			'title' => 'Requires Update Permission',
			'slug' => 'requires-update-permission',
			'status' => 'draft',
			'layout_json' => [],
		]);

		$this->actingAs($creator, 'web')
			->postJson('/api/v1/admin/page-builder/editor/access-token', [
				'page_id' => $page->id,
			])
			->assertForbidden();

		$editor = $this->makeAdminWithPermissions([
			'page-builder.page.update',
		]);

		$this->actingAs($editor, 'web')
			->postJson('/api/v1/admin/page-builder/editor/access-token', [
				'page_id' => $page->id,
			])
			->assertOk()
			->assertJsonPath('success', true);
	}

	public function test_editor_verify_token_endpoint_accepts_valid_token(): void
	{
		$admin = $this->makeAdminWithPermissions([
			'page-builder.page.create',
		]);

		$tokenResponse = $this->actingAs($admin, 'web')
			->postJson('/api/v1/admin/page-builder/editor/access-token', []);

		$accessToken = (string) $tokenResponse->json('data.access_token');

		$this->postJson('/api/v1/admin/page-builder/editor/verify-token', [
			'token' => $accessToken,
		])
			->assertOk()
			->assertJsonPath('success', true)
			->assertJsonPath('data.valid', true);
	}

	public function test_editor_verify_token_endpoint_rejects_invalid_token(): void
	{
		$this->postJson('/api/v1/admin/page-builder/editor/verify-token', [
			'token' => 'invalid-token',
		])
			->assertStatus(401)
			->assertJsonPath('success', false)
			->assertJsonPath('code', 'UNAUTHORIZED');
	}

	public function test_editor_verify_token_endpoint_rejects_replay_when_consume_enabled(): void
	{
		$admin = $this->makeAdminWithPermissions([
			'page-builder.page.create',
		]);

		$tokenResponse = $this->actingAs($admin, 'web')
			->postJson('/api/v1/admin/page-builder/editor/access-token', []);

		$accessToken = (string) $tokenResponse->json('data.access_token');

		$this->postJson('/api/v1/admin/page-builder/editor/verify-token', [
			'token' => $accessToken,
			'consume' => true,
		])
			->assertOk()
			->assertJsonPath('success', true);

		$this->postJson('/api/v1/admin/page-builder/editor/verify-token', [
			'token' => $accessToken,
			'consume' => true,
		])
			->assertStatus(401)
			->assertJsonPath('code', 'UNAUTHORIZED');
	}

	public function test_editor_contract_endpoint_returns_runtime_contract_and_endpoints(): void
	{
		$response = $this->getJson('/api/v1/admin/page-builder/editor/contract');

		$response
			->assertOk()
			->assertJsonPath('success', true)
			->assertJsonPath('data.contract.namespace', 'pagify:editor')
			->assertJsonPath('data.contract.protocol_version', 1)
			->assertJsonPath('data.contract.messages.parent_to_child.init', 'pagify:editor:init')
			->assertJsonPath('data.endpoints.token_access', route('page-builder.api.v1.admin.editor.access-token'))
			->assertJsonPath('data.endpoints.token_verify', route('page-builder.api.v1.admin.editor.verify-token'))
			->assertJsonPath('data.endpoints.builder_data', route('page-builder.api.v1.admin.editor.builder-data'))
			->assertJsonPath('data.endpoints.media_assets', route('page-builder.api.v1.admin.editor.media.assets'))
			->assertJsonPath('data.endpoints.media_assets_upload', route('page-builder.api.v1.admin.editor.media.assets.upload'));
	}

	public function test_editor_builder_data_endpoint_returns_layout_context_for_valid_token(): void
	{
		$admin = $this->makeAdminWithPermissions([
			'page-builder.page.create',
			'page-builder.page.update',
		]);

		$page = Page::query()->create([
			'title' => 'Builder Data',
			'slug' => 'builder-data',
			'status' => 'draft',
			'layout_json' => [
				'type' => 'webstudio',
				'webstudio' => [
					'html' => '<main data-pbx-content-slot="true"><section>Builder data</section></main>',
				],
			],
			'seo_meta_json' => [
				'title' => 'Builder Data SEO',
			],
		]);

		$tokenResponse = $this->actingAs($admin, 'web')
			->postJson('/api/v1/admin/page-builder/editor/access-token', [
				'page_id' => $page->id,
			]);

		$accessToken = (string) $tokenResponse->json('data.access_token');

		$this->postJson('/api/v1/admin/page-builder/editor/builder-data', [
			'token' => $accessToken,
		])
			->assertOk()
			->assertJsonPath('success', true)
			->assertJsonPath('data.page.id', $page->id)
			->assertJsonPath('data.layout.type', 'webstudio')
			->assertJsonStructure([
				'success',
				'data' => [
					'context' => ['active_theme', 'breakpoints', 'blocks', 'canvas_styles'],
				],
			])
			->assertJsonPath('data.context.breakpoints.0', 'desktop');
	}

	public function test_editor_builder_data_endpoint_rejects_invalid_token(): void
	{
		$this->postJson('/api/v1/admin/page-builder/editor/builder-data', [
			'token' => 'invalid-token',
		])
			->assertStatus(401)
			->assertJsonPath('success', false)
			->assertJsonPath('code', 'UNAUTHORIZED');
	}

	public function test_editor_builder_data_endpoint_returns_forbidden_when_site_scope_mismatches(): void
	{
		/** @var EditorAccessTokenService $tokenService */
		$tokenService = app(EditorAccessTokenService::class);

		$issued = $tokenService->issue([
			'admin_id' => null,
			'site_id' => 999999,
			'theme' => 'default',
			'scopes' => ['page:read'],
		]);

		$token = (string) ($issued['token'] ?? '');

		$this->postJson('/api/v1/admin/page-builder/editor/builder-data', [
			'token' => $token,
		])
			->assertStatus(403)
			->assertJsonPath('code', 'FORBIDDEN');
	}

	public function test_editor_media_assets_endpoint_returns_media_for_valid_token(): void
	{
		$admin = $this->makeAdminWithPermissions([
			'page-builder.page.create',
		]);

		MediaAsset::query()->create([
			'uuid' => '223e4567-e89b-12d3-a456-426614174001',
			'disk' => 'public',
			'path' => 'media/page-builder/phase2-list.jpg',
			'filename' => 'phase2-list.jpg',
			'original_name' => 'phase2-list.jpg',
			'mime_type' => 'image/jpeg',
			'extension' => 'jpg',
			'kind' => 'image',
		]);

		$tokenResponse = $this->actingAs($admin, 'web')
			->postJson('/api/v1/admin/page-builder/editor/access-token', []);

		$accessToken = (string) $tokenResponse->json('data.access_token');

		$this->postJson('/api/v1/admin/page-builder/editor/media/assets', [
			'token' => $accessToken,
			'q' => 'phase2-list',
		])
			->assertOk()
			->assertJsonPath('success', true)
			->assertJsonPath('meta.pagination.current_page', 1);
	}

	public function test_editor_media_upload_endpoint_uploads_asset_for_valid_token(): void
	{
		Storage::fake('public');

		$admin = $this->makeAdminWithPermissions([
			'page-builder.page.create',
		]);

		$tokenResponse = $this->actingAs($admin, 'web')
			->postJson('/api/v1/admin/page-builder/editor/access-token', []);

		$accessToken = (string) $tokenResponse->json('data.access_token');

		$response = $this->post('/api/v1/admin/page-builder/editor/media/assets/upload', [
			'token' => $accessToken,
			'file' => UploadedFile::fake()->image('phase2-upload.jpg', 200, 200),
		]);

		$response
			->assertStatus(201)
			->assertJsonPath('success', true)
			->assertJsonPath('data.original_name', 'phase2-upload.jpg');
	}

	public function test_editor_media_endpoints_return_forbidden_when_scope_is_missing(): void
	{
		/** @var EditorAccessTokenService $tokenService */
		$tokenService = app(EditorAccessTokenService::class);

		$issued = $tokenService->issue([
			'admin_id' => null,
			'site_id' => null,
			'theme' => 'default',
			'scopes' => ['page:read'],
		]);

		$token = (string) ($issued['token'] ?? '');

		$this->postJson('/api/v1/admin/page-builder/editor/media/assets', [
			'token' => $token,
		])
			->assertStatus(403)
			->assertJsonPath('code', 'FORBIDDEN');

		$this->post('/api/v1/admin/page-builder/editor/media/assets/upload', [
			'token' => $token,
			'file' => UploadedFile::fake()->image('missing-scope.jpg', 200, 200),
		])
			->assertStatus(403)
			->assertJsonPath('code', 'FORBIDDEN');
	}

	public function test_editor_media_assets_endpoint_returns_forbidden_when_site_scope_mismatches(): void
	{
		/** @var EditorAccessTokenService $tokenService */
		$tokenService = app(EditorAccessTokenService::class);

		$issued = $tokenService->issue([
			'admin_id' => null,
			'site_id' => 999999,
			'theme' => 'default',
			'scopes' => ['media:read'],
		]);

		$token = (string) ($issued['token'] ?? '');

		$this->postJson('/api/v1/admin/page-builder/editor/media/assets', [
			'token' => $token,
		])
			->assertStatus(403)
			->assertJsonPath('code', 'FORBIDDEN');
	}

	public function test_editor_host_route_requires_authentication_and_permission(): void
	{
		$this->get('/admin/page-builder/editor-host')->assertRedirect();

		/** @var Admin $withoutPermission */
		$withoutPermission = Admin::factory()->create();

		$this->actingAs($withoutPermission, 'web')
			->get('/admin/page-builder/editor-host')
			->assertForbidden();

		$allowed = $this->makeAdminWithPermissions([
			'page-builder.page.viewAny',
		]);

		$this->actingAs($allowed, 'web')
			->get('/admin/page-builder/editor-host?accessToken=test-token')
			->assertOk()
			->assertSee('PagifyPageBuilderEditorBoot', false)
			->assertSee('test-token', false);

		$creatorOnly = $this->makeAdminWithPermissions([
			'page-builder.page.create',
		]);

		$this->actingAs($creatorOnly, 'web')
			->get('/admin/page-builder/editor-host?accessToken=test-token')
			->assertOk();

		config()->set('page-builder.webstudio_iframe.runtime_mode', 'upstream-experimental');

		$this->actingAs($allowed, 'web')
			->get('/admin/page-builder/editor-host?accessToken=test-token')
			->assertOk()
			->assertSee('upstream-experimental', false);
	}

	public function test_create_payload_uses_module_editor_host_route_for_iframe(): void
	{
		config()->set('page-builder.webstudio_iframe.origin', '');

		$admin = $this->makeAdminWithPermissions([
			'page-builder.page.create',
		]);

		$response = $this->actingAs($admin, 'web')->get('/admin/page-builder/pages/create', [
			'X-Inertia' => 'true',
			'X-Requested-With' => 'XMLHttpRequest',
		]);

		$response
			->assertOk()
			->assertHeader('X-Inertia', 'true')
			->assertJsonPath('props.editor.iframe.enabled', true);

		$iframeUrl = (string) $response->json('props.editor.iframe.url');

		$this->assertStringStartsWith(route('page-builder.admin.editor.host'), $iframeUrl);
		$this->assertStringContainsString('accessToken=', $iframeUrl);

		$query = parse_url($iframeUrl, PHP_URL_QUERY);
		parse_str(is_string($query) ? $query : '', $params);

		$expectedOrigin = rtrim((string) config('app.url'), '/');

		$this->assertSame($expectedOrigin, $params['parentOrigin'] ?? null);
	}

	public function test_page_media_usage_is_synced_on_update_and_cleared_on_delete(): void
	{
		$admin = $this->makeAdminWithPermissions([
			'page-builder.page.create',
			'page-builder.page.update',
			'page-builder.page.delete',
		]);

		$asset = MediaAsset::query()->create([
			'uuid' => '123e4567-e89b-12d3-a456-426614174000',
			'disk' => 'public',
			'path' => 'media/page-builder/hero.jpg',
			'filename' => 'hero.jpg',
			'original_name' => 'hero.jpg',
			'mime_type' => 'image/jpeg',
			'extension' => 'jpg',
			'kind' => 'image',
		]);

		$this->actingAs($admin, 'web')->post('/admin/page-builder/pages', [
			'title' => 'Media Sync',
			'slug' => 'media-sync',
			'status' => 'draft',
			'layout' => [
				'type' => 'webstudio',
				'webstudio' => [
					'html' => '<main data-pbx-content-slot="true"><img src="/storage/media/page-builder/hero.jpg" data-asset-uuid="123e4567-e89b-12d3-a456-426614174000" /></main>',
					'assets' => ['123e4567-e89b-12d3-a456-426614174000'],
				],
			],
		])->assertRedirect();

		$page = Page::query()->where('slug', 'media-sync')->firstOrFail();

		$this->assertTrue(MediaUsage::query()
			->where('context_type', 'page_builder.page')
			->where('context_id', (string) $page->id)
			->where('asset_id', $asset->id)
			->exists());

		$this->actingAs($admin, 'web')->put('/admin/page-builder/pages/' . $page->id, [
			'title' => 'Media Sync Updated',
			'slug' => 'media-sync',
			'status' => 'draft',
			'layout' => [
				'type' => 'webstudio',
				'webstudio' => [
					'html' => '<main data-pbx-content-slot="true"><p>No media</p></main>',
				],
			],
		])->assertRedirect();

		$this->assertFalse(MediaUsage::query()
			->where('context_type', 'page_builder.page')
			->where('context_id', (string) $page->id)
			->where('asset_id', $asset->id)
			->exists());

		$this->actingAs($admin, 'web')->delete('/admin/page-builder/pages/' . $page->id)->assertRedirect();

		$this->assertFalse(MediaUsage::query()
			->where('context_type', 'page_builder.page')
			->where('context_id', (string) $page->id)
			->exists());
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
