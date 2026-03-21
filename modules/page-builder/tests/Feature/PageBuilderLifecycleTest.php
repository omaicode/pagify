<?php

namespace Pagify\PageBuilder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Pagify\Core\Models\Admin;
use Pagify\Core\Models\PluginState;
use Pagify\Core\Services\EventBus;
use Pagify\Media\Models\MediaAsset;
use Pagify\Media\Models\MediaUsage;
use Pagify\PageBuilder\Models\Page;
use Pagify\PageBuilder\Models\PageBuilderInstance;
use Pagify\PageBuilder\Models\PageBuilderState;
use Pagify\PageBuilder\Services\EditorAccessTokenService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PageBuilderLifecycleTest extends TestCase
{
	use RefreshDatabase;

	public function test_create_update_publish_page_lifecycle(): void
	{
		$accessToken = $this->issueEditorToken([
			'page:read',
			'page:write',
			'page:publish',
		]);

		$this->withEditorToken($accessToken)->postJson('/api/v1/admin/page-builder/pages', [
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
		])->assertStatus(201);

		$page = Page::query()->where('slug', 'landing-page')->firstOrFail();
		$this->assertSame('draft', $page->status);

		$this->withEditorToken($accessToken)->putJson('/api/v1/admin/page-builder/pages/' . $page->id, [
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
		])->assertOk();

		$this->withEditorToken($accessToken)
			->postJson('/api/v1/admin/page-builder/pages/' . $page->id . '/publish')
			->assertOk();

		$page->refresh();
		$this->assertSame('published', $page->status);
		$this->assertNotNull($page->published_at);
	}

	public function test_registry_api_returns_blocks_and_breakpoints(): void
	{
		$accessToken = $this->issueEditorToken(['page:read']);

		$response = $this->withEditorToken($accessToken)->get('/api/v1/admin/page-builder/registry');

		$response
			->assertOk()
			->assertJsonPath('success', true)
			->assertJsonStructure([
				'success',
				'data' => ['blocks', 'breakpoints'],
			]);
	}

	public function test_registry_api_includes_event_bus_registered_components_with_owner_prefix(): void
	{
		/** @var EventBus $eventBus */
		$eventBus = app(EventBus::class);
		$eventBus->onHook('page-builder.webstudio.components', static fn (): array => [
			[
				'key' => 'hero-banner',
				'label' => 'Hero Banner',
				'owner' => 'marketing-plugin',
				'owner_type' => 'plugin',
				'category' => 'Marketing',
			],
		]);

		$accessToken = $this->issueEditorToken(['page:read']);

		$this->withEditorToken($accessToken)
			->get('/api/v1/admin/page-builder/registry')
			->assertOk()
			->assertJsonFragment([
				'key' => 'marketing-plugin:hero-banner',
				'owner' => 'marketing-plugin',
				'owner_type' => 'plugin',
				'source' => 'hook',
			]);
	}

	public function test_webstudio_data_payload_exposes_registered_components(): void
	{
		/** @var EventBus $eventBus */
		$eventBus = app(EventBus::class);
		$eventBus->onHook('page-builder.webstudio.components', static fn (): array => [
			[
				'key' => 'hero-banner',
				'label' => 'Hero Banner',
				'owner' => 'marketing-plugin',
				'owner_type' => 'plugin',
				'category' => 'Marketing',
			],
		]);

		$page = Page::query()->create([
			'title' => 'Builder Data',
			'slug' => 'builder-data-registry',
			'status' => 'draft',
			'layout_json' => [],
		]);

		$accessToken = $this->issueEditorToken(['page:read'], pageId: $page->id, pageSlug: $page->slug);

		$this->withEditorToken($accessToken)
			->getJson('/api/v1/admin/page-builder/data/' . $page->id)
			->assertOk()
			->assertJsonFragment([
				'key' => 'marketing-plugin:hero-banner',
				'owner' => 'marketing-plugin',
				'owner_type' => 'plugin',
			]);
	}

	public function test_demo_plugin_webstudio_components_are_loaded_end_to_end(): void
	{
		$pluginRoot = base_path('plugins/demo-webstudio-register');
		$this->assertFileExists($pluginRoot . '/plugin.json');
		$this->assertFileExists($pluginRoot . '/config/webstudio-components.php');

		$manifestRaw = file_get_contents($pluginRoot . '/plugin.json');
		$this->assertIsString($manifestRaw);
		$manifest = json_decode($manifestRaw, true);
		$this->assertIsArray($manifest);

		PluginState::query()->updateOrCreate(
			['slug' => 'demo-webstudio-register'],
			[
				'name' => 'Demo Webstudio Register',
				'version' => '0.1.0',
				'source_type' => 'local',
				'root_path' => $pluginRoot,
				'enabled' => true,
				'is_installed' => true,
				'is_compatible' => true,
				'manifest' => $manifest,
			]
		);

		$page = Page::query()->create([
			'title' => 'Plugin Registry Page',
			'slug' => 'plugin-registry-page',
			'status' => 'draft',
			'layout_json' => [],
		]);

		$accessToken = $this->issueEditorToken(['page:read'], pageId: $page->id, pageSlug: $page->slug);

		$this->withEditorToken($accessToken)
			->get('/api/v1/admin/page-builder/registry')
			->assertOk()
			->assertJsonFragment([
				'key' => 'demo-webstudio-register:hero-banner',
				'label' => 'Demo Hero Banner',
				'owner' => 'demo-webstudio-register',
				'owner_type' => 'plugin',
			]);

		$this->withEditorToken($accessToken)
			->getJson('/api/v1/admin/page-builder/data/' . $page->id)
			->assertOk()
			->assertJsonFragment([
				'key' => 'demo-webstudio-register:cta-strip',
				'label' => 'Demo CTA Strip',
				'owner' => 'demo-webstudio-register',
				'owner_type' => 'plugin',
			]);
	}

	public function test_create_and_publish_page_with_webstudio_layout_payload(): void
	{
		$accessToken = $this->issueEditorToken([
			'page:read',
			'page:write',
			'page:publish',
		]);

		$this->withEditorToken($accessToken)->postJson('/api/v1/admin/page-builder/pages', [
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
		])->assertStatus(201);

		$page = Page::query()->where('slug', 'webstudio-landing')->firstOrFail();
		$this->assertSame('published', $page->status);
		$this->assertNotNull($page->published_at);
		$this->assertStringContainsString('Webstudio content', (string) data_get($page->layout_json, 'webstudio.html', ''));
	}

	public function test_editor_access_token_endpoint_returns_token_for_create_context(): void
	{
		$bootstrapToken = $this->issueEditorToken(['page:read']);

		$response = $this->withEditorToken($bootstrapToken)
			->postJson('/api/v1/admin/page-builder/editor/access-token', []);

		$response
			->assertOk()
			->assertJsonPath('success', true)
			->assertJsonStructure([
				'success',
				'data' => ['access_token', 'expires_at'],
			]);
	}

	public function test_editor_access_token_endpoint_rejects_page_scope_mismatch(): void
	{
		$pageA = Page::query()->create([
			'title' => 'Page A',
			'slug' => 'page-a',
			'status' => 'draft',
			'layout_json' => [],
		]);
		$pageB = Page::query()->create([
			'title' => 'Page B',
			'slug' => 'page-b',
			'status' => 'draft',
			'layout_json' => [],
		]);

		$pageScopedToken = $this->issueEditorToken(['page:read'], pageId: $pageA->id, pageSlug: $pageA->slug);

		$this->withEditorToken($pageScopedToken)
			->postJson('/api/v1/admin/page-builder/editor/access-token', [
				'page_id' => $pageB->id,
			])
			->assertStatus(403)
			->assertJsonPath('code', 'FORBIDDEN');

		$this->withEditorToken($pageScopedToken)
			->postJson('/api/v1/admin/page-builder/editor/access-token', [
				'page_id' => $pageA->id,
			])
			->assertOk()
			->assertJsonPath('success', true);
	}

	public function test_editor_verify_token_endpoint_accepts_valid_token(): void
	{
		$accessToken = $this->issueEditorToken(['page:read']);

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
		$accessToken = $this->issueEditorToken(['page:read']);

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

	public function test_webstudio_cgi_image_route_redirects_same_origin_absolute_asset_url(): void
	{
		$response = $this->get('/cgi/image/http%3A%2F%2F127.0.0.1%3A8000%2Fstorage%2Fmedia%2F2026%2F03%2Fdemo.png?width=128&quality=80&format=auto');

		$response
			->assertRedirect('http://127.0.0.1/storage/media/2026/03/demo.png');
	}

	public function test_webstudio_cgi_image_route_redirects_relative_asset_path(): void
	{
		$response = $this->get('/cgi/image/storage/media/2026/03/demo.png?width=128&quality=80&format=auto');

		$response
			->assertRedirect('/storage/media/2026/03/demo.png');
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

		$accessToken = $this->issueEditorToken(['page:read'], pageId: $page->id, pageSlug: $page->slug);

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

		$accessToken = $this->issueEditorToken(['media:read']);

		$this->postJson('/api/v1/admin/page-builder/editor/media/assets', [
			'token' => $accessToken,
			'q' => 'phase2-list',
		])
			->assertOk()
			->assertJsonPath('success', true)
			->assertJsonPath('meta.pagination.current_page', 1);
	}

	public function test_webstudio_compat_assets_return_relative_asset_name_for_loader(): void
	{
		$asset = MediaAsset::query()->create([
			'uuid' => '323e4567-e89b-12d3-a456-426614174001',
			'disk' => 'public',
			'path' => 'media/2026/03/demo.png',
			'filename' => 'demo.png',
			'original_name' => 'demo.png',
			'mime_type' => 'image/png',
			'extension' => 'png',
			'size_bytes' => 1234,
			'kind' => 'image',
		]);

		$accessToken = $this->issueEditorToken(['media:read']);

		$this->withHeaders([
			'x-auth-token' => $accessToken,
		])->getJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/assets?projectId=pagify-local')
			->assertOk()
			->assertJsonPath('0.id', (string) $asset->id)
			->assertJsonPath('0.name', 'storage/media/2026/03/demo.png');
	}

	public function test_webstudio_compat_asset_delete_removes_asset_from_system(): void
	{
		$asset = MediaAsset::query()->create([
			'uuid' => '423e4567-e89b-12d3-a456-426614174001',
			'disk' => 'public',
			'path' => 'media/2026/03/delete-me.png',
			'filename' => 'delete-me.png',
			'original_name' => 'delete-me.png',
			'mime_type' => 'image/png',
			'extension' => 'png',
			'size_bytes' => 1234,
			'kind' => 'image',
		]);

		$accessToken = $this->issueEditorToken(['media:write']);

		$this->withHeaders([
			'x-auth-token' => $accessToken,
		])->deleteJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/assets/' . $asset->id)
			->assertOk()
			->assertJsonPath('deleted', true)
			->assertJsonPath('id', (string) $asset->id);

		$this->assertNull(MediaAsset::query()->find($asset->id));
	}

	public function test_editor_media_upload_endpoint_uploads_asset_for_valid_token(): void
	{
		Storage::fake('public');

		$accessToken = $this->issueEditorToken(['media:write']);

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

	public function test_webstudio_rest_data_and_patch_endpoints_accept_valid_token(): void
	{
		$page = Page::query()->create([
			'title' => 'Compat Data',
			'slug' => 'compat-data',
			'status' => 'draft',
			'layout_json' => [
				'type' => 'webstudio',
				'webstudio' => [
					'html' => '<main>Compat Data</main>',
				],
			],
		]);

		$accessToken = $this->issueEditorToken(
			['page:read', 'page:write'],
			pageId: $page->id,
			pageSlug: $page->slug
		);

		$dataResponse = $this->withHeaders([
			'x-auth-token' => $accessToken,
		])->getJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/data/' . $page->id);

		$dataResponse
			->assertOk()
			->assertJsonPath('projectId', (string) $page->id)
			->assertJsonPath('project.id', (string) $page->id)
			->assertJsonPath('pages.homePage.id', (string) $page->id)
			->assertJsonPath('breakpoints.0.id', 'aqVX0VTx9bxHqRd0MS3a3');

		$version = (int) $dataResponse->json('version');

		$this->withHeaders([
			'x-auth-token' => $accessToken,
		])->postJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/patch', [
			'projectId' => (string) $page->id,
			'buildId' => 'build-' . $page->id,
			'version' => $version,
			'transactions' => [],
		])
			->assertOk()
			->assertJsonPath('status', 'ok');

		$this->withHeaders([
			'x-auth-token' => $accessToken,
		])->postJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/trpc/domain.project?batch=1', [])
			->assertOk()
			->assertJsonPath('0.result.data.success', true)
			->assertJsonPath('0.result.data.project.id', 'default');
	}

	public function test_webstudio_patch_persists_instances_by_page_and_data_returns_root_page_instances_only(): void
	{
		$rootPage = Page::query()->create([
			'title' => 'Root Page',
			'slug' => 'root-page',
			'status' => 'draft',
			'layout_json' => [],
		]);

		$secondPage = Page::query()->create([
			'title' => 'Second Page',
			'slug' => 'second-page',
			'status' => 'draft',
			'layout_json' => [],
		]);

		$accessToken = $this->issueEditorToken(
			['page:read', 'page:write'],
			pageId: $secondPage->id,
			pageSlug: $secondPage->slug
		);

		$dataResponse = $this->withHeaders([
			'x-auth-token' => $accessToken,
		])->getJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/data/unified');

		$dataResponse->assertOk()->assertJsonPath('projectId', 'unified');
		$version = (int) $dataResponse->json('version');

		$this->withHeaders([
			'x-auth-token' => $accessToken,
		])->postJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/patch', [
			'projectId' => 'unified',
			'pageId' => (string) $secondPage->id,
			'buildId' => 'build-unified',
			'version' => $version,
			'transactions' => [],
			'state' => [
				'instances' => [
					[
						'id' => 'root-' . (string) $rootPage->id,
						'type' => 'instance',
						'component' => 'ws:element',
						'children' => [['type' => 'id', 'value' => 'root-child']],
						'tag' => 'body',
					],
					[
						'id' => 'root-child',
						'type' => 'instance',
						'component' => 'ws:element',
						'children' => [],
						'tag' => 'main',
					],
					[
						'id' => 'root-' . (string) $secondPage->id,
						'type' => 'instance',
						'component' => 'ws:element',
						'children' => [['type' => 'id', 'value' => 'second-child']],
						'tag' => 'body',
					],
					[
						'id' => 'second-child',
						'type' => 'instance',
						'component' => 'ws:element',
						'children' => [],
						'tag' => 'section',
					],
				],
			],
		])
			->assertOk()
			->assertJsonPath('status', 'ok');

		$this->assertDatabaseHas('pb_page_builder_instances', [
			'project_id' => 'unified',
			'page_id' => $secondPage->id,
		]);

		$storedInstances = PageBuilderInstance::query()
			->withoutGlobalScopes()
			->where('project_id', 'unified')
			->where('page_id', $secondPage->id)
			->value('instances_json');

		$this->assertIsArray($storedInstances);
		$this->assertSame('root-' . (string) $secondPage->id, (string) ($storedInstances[0]['id'] ?? ''));

		$rootDataResponse = $this->withHeaders([
			'x-auth-token' => $accessToken,
		])->getJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/data/unified');

		$rootDataResponse
			->assertOk()
			->assertJsonPath('pages.homePage.id', (string) $rootPage->id)
			->assertJsonPath('instances.0.id', 'root-' . (string) $rootPage->id);

		$instanceIds = array_values(array_map(
			static fn (array $instance): string => (string) ($instance['id'] ?? ''),
			(array) $rootDataResponse->json('instances', [])
		));

		$this->assertNotContains('root-' . (string) $secondPage->id, $instanceIds);
	}

	public function test_webstudio_page_data_endpoint_returns_selected_page_instances(): void
	{
		$rootPage = Page::query()->create([
			'title' => 'Root Page',
			'slug' => 'root-page',
			'status' => 'draft',
			'layout_json' => [],
		]);

		$secondPage = Page::query()->create([
			'title' => 'Second Page',
			'slug' => 'second-page',
			'status' => 'draft',
			'layout_json' => [],
		]);

		$accessToken = $this->issueEditorToken(
			['page:read', 'page:write'],
			pageId: $secondPage->id,
			pageSlug: $secondPage->slug
		);

		$initialDataResponse = $this->withHeaders([
			'x-auth-token' => $accessToken,
		])->getJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/data/unified');

		$initialDataResponse->assertOk();
		$version = (int) $initialDataResponse->json('version');

		$this->withHeaders([
			'x-auth-token' => $accessToken,
		])->postJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/patch', [
			'projectId' => 'unified',
			'pageId' => (string) $secondPage->id,
			'buildId' => 'build-unified',
			'version' => $version,
			'transactions' => [],
			'state' => [
				'instances' => [
					[
						'id' => 'root-' . (string) $rootPage->id,
						'type' => 'instance',
						'component' => 'ws:element',
						'children' => [['type' => 'id', 'value' => 'root-child']],
						'tag' => 'body',
					],
					[
						'id' => 'root-child',
						'type' => 'instance',
						'component' => 'ws:element',
						'children' => [],
						'tag' => 'main',
					],
					[
						'id' => 'root-' . (string) $secondPage->id,
						'type' => 'instance',
						'component' => 'ws:element',
						'children' => [['type' => 'id', 'value' => 'second-child']],
						'tag' => 'body',
					],
					[
						'id' => 'second-child',
						'type' => 'instance',
						'component' => 'ws:element',
						'children' => [],
						'tag' => 'section',
					],
				],
			],
		])->assertOk()->assertJsonPath('status', 'ok');

		$pageDataResponse = $this->withHeaders([
			'x-auth-token' => $accessToken,
		])->getJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/data/unified/pages/' . $secondPage->id);

		$pageDataResponse
			->assertOk()
			->assertJsonPath('projectId', 'unified')
			->assertJsonPath('pageId', (string) $secondPage->id)
			->assertJsonPath('instances.0.id', 'root-' . (string) $secondPage->id);

		$pageInstanceIds = array_values(array_map(
			static fn (array $instance): string => (string) ($instance['id'] ?? ''),
			(array) $pageDataResponse->json('instances', [])
		));

		$this->assertContains('second-child', $pageInstanceIds);
		$this->assertNotContains('root-' . (string) $rootPage->id, $pageInstanceIds);
	}

	public function test_webstudio_patch_uses_page_root_instance_id_payload_when_extracting_instances(): void
	{
		$page = Page::query()->create([
			'title' => 'Custom Root Page',
			'slug' => 'custom-root-page',
			'status' => 'draft',
			'layout_json' => [],
		]);

		$accessToken = $this->issueEditorToken(
			['page:read', 'page:write'],
			pageId: $page->id,
			pageSlug: $page->slug
		);

		$dataResponse = $this->withHeaders([
			'x-auth-token' => $accessToken,
		])->getJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/data/unified');

		$dataResponse->assertOk();
		$version = (int) $dataResponse->json('version');

		$customRootId = 'custom-root-' . (string) $page->id;

		$this->withHeaders([
			'x-auth-token' => $accessToken,
		])->postJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/patch', [
			'projectId' => 'unified',
			'pageId' => (string) $page->id,
			'pageRootInstanceId' => $customRootId,
			'buildId' => 'build-unified',
			'version' => $version,
			'transactions' => [],
			'state' => [
				'instances' => [
					[
						'id' => $customRootId,
						'type' => 'instance',
						'component' => 'ws:element',
						'children' => [['type' => 'id', 'value' => 'custom-child']],
						'tag' => 'body',
					],
					[
						'id' => 'custom-child',
						'type' => 'instance',
						'component' => 'ws:element',
						'children' => [],
						'tag' => 'section',
					],
				],
			],
		])->assertOk()->assertJsonPath('status', 'ok');

		$storedInstances = PageBuilderInstance::query()
			->withoutGlobalScopes()
			->where('project_id', 'unified')
			->where('page_id', $page->id)
			->value('instances_json');

		$this->assertIsArray($storedInstances);
		$this->assertSame($customRootId, (string) ($storedInstances[0]['id'] ?? ''));
	}

	public function test_webstudio_page_data_endpoint_injects_fallback_root_when_persisted_snapshot_has_no_page_root(): void
	{
		$rootPage = Page::query()->create([
			'title' => 'Root Page',
			'slug' => 'root-page',
			'status' => 'draft',
			'layout_json' => [],
		]);

		$secondPage = Page::query()->create([
			'title' => 'Second Page',
			'slug' => 'second-page',
			'status' => 'draft',
			'layout_json' => [],
		]);

		$accessToken = $this->issueEditorToken(
			['page:read'],
			pageId: $secondPage->id,
			pageSlug: $secondPage->slug
		);

		PageBuilderState::query()->create([
			'page_id' => $secondPage->id,
			'site_id' => $secondPage->site_id,
			'build_id' => 'build-unified',
			'version' => 3,
			'data_json' => [
				'instances' => [
					[
						'id' => 'root-' . (string) $rootPage->id,
						'type' => 'instance',
						'component' => 'ws:element',
						'children' => [],
						'tag' => 'body',
					],
				],
			],
		]);

		$response = $this->withHeaders([
			'x-auth-token' => $accessToken,
		])->getJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/data/unified/pages/' . $secondPage->id);

		$response
			->assertOk()
			->assertJsonPath('pageId', (string) $secondPage->id)
			->assertJsonPath('instances.0.id', 'root-' . (string) $secondPage->id);
	}

	public function test_page_folder_api_supports_crud_reparent_and_page_reorder_with_server_hydration(): void
	{
		$homePage = Page::query()->create([
			'title' => 'Home',
			'slug' => 'home',
			'status' => 'draft',
			'layout_json' => [],
		]);

		$childPage = Page::query()->create([
			'title' => 'Child',
			'slug' => 'child',
			'status' => 'draft',
			'layout_json' => [],
		]);

		$accessToken = $this->issueEditorToken(['page:read', 'page:write']);

		$createResponse = $this->withHeaders([
			'x-auth-token' => $accessToken,
		])->postJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/folders', [
			'folder_id' => 'marketing',
			'name' => 'Marketing',
			'slug' => 'marketing',
			'parent_folder_id' => 'root',
		]);

		$createResponse
			->assertStatus(201)
			->assertJsonPath('success', true)
			->assertJsonPath('data.id', 'marketing');

		$this->withHeaders([
			'x-auth-token' => $accessToken,
		])->postJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/folders', [
			'folder_id' => 'campaigns',
			'name' => 'Campaigns',
			'slug' => 'campaigns',
			'parent_folder_id' => 'root',
		])->assertStatus(201);

		$this->withHeaders([
			'x-auth-token' => $accessToken,
		])->postJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/folders/move', [
			'item_type' => 'folder',
			'item_id' => 'marketing',
			'parent_folder_id' => 'campaigns',
			'index_within_children' => 0,
		])->assertOk()->assertJsonPath('data.status', 'ok');

		$this->withHeaders([
			'x-auth-token' => $accessToken,
		])->postJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/folders/move', [
			'item_type' => 'page',
			'item_id' => (string) $childPage->id,
			'parent_folder_id' => 'marketing',
			'index_within_children' => 0,
		])->assertOk()->assertJsonPath('data.status', 'ok');

		$this->withHeaders([
			'x-auth-token' => $accessToken,
		])->putJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/folders/marketing', [
			'name' => 'Marketing Updated',
			'slug' => 'marketing-updated',
		])->assertOk()->assertJsonPath('data.slug', 'marketing-updated');

		$hydrateResponse = $this->withHeaders([
			'x-auth-token' => $accessToken,
		])->getJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/data/unified');

		$hydrateResponse
			->assertOk()
			->assertJsonPath('pages.homePage.id', (string) $homePage->id)
			->assertJsonFragment([
				'id' => 'campaigns',
				'name' => 'Campaigns',
			])
			->assertJsonFragment([
				'id' => 'marketing',
				'name' => 'Marketing Updated',
			]);

		$this->withHeaders([
			'x-auth-token' => $accessToken,
		])->deleteJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/folders/marketing')
			->assertOk()
			->assertJsonPath('data.deleted', true);

		$childPage->refresh();
		$this->assertNull($childPage->folder_id);

		$this->withHeaders([
			'x-auth-token' => $accessToken,
		])->getJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/data/unified')
			->assertOk()
			->assertJsonPath('pages.homePage.meta.status', 'draft');
	}

	public function test_webstudio_rest_data_uses_live_page_status_from_database_not_persisted_pages_snapshot(): void
	{
		$page = Page::query()->create([
			'title' => 'Live Status Page',
			'slug' => 'live-status-page',
			'status' => 'draft',
			'layout_json' => [],
		]);

		PageBuilderState::query()->create([
			'page_id' => $page->id,
			'site_id' => $page->site_id,
			'build_id' => 'build-' . $page->id,
			'version' => 2,
			'data_json' => [
				'pages' => [
					'homePage' => [
						'id' => (string) $page->id,
						'name' => 'Stale Published',
						'path' => '',
						'title' => '"Stale Published"',
						'meta' => [
							'status' => 'published',
						],
						'rootInstanceId' => 'root-' . (string) $page->id,
					],
					'pages' => [],
					'folders' => [[
						'id' => 'root',
						'name' => 'Root',
						'slug' => '',
						'children' => [(string) $page->id],
					]],
				],
			],
		]);

		$accessToken = $this->issueEditorToken(['page:read'], pageId: $page->id, pageSlug: $page->slug);

		$this->withHeaders([
			'x-auth-token' => $accessToken,
		])->getJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/data/' . $page->id)
			->assertOk()
			->assertJsonPath('pages.homePage.name', 'Live Status Page')
			->assertJsonPath('pages.homePage.meta.status', 'draft');
	}

	public function test_webstudio_rest_data_endpoint_works_without_persisted_pages_when_database_is_empty(): void
	{
		Page::query()->delete();

		$accessToken = $this->issueEditorToken(['page:read']);

		$response = $this->withHeaders([
			'x-auth-token' => $accessToken,
		])->getJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/data/pagify-local');

		$response
			->assertOk()
			->assertJsonPath('projectId', 'pagify-local')
			->assertJsonPath('pages.homePage.path', '')
			->assertJsonPath('pages.homePage.id', 'home-pagify-local');

		$this->assertNull(Page::query()->first());
	}

	public function test_webstudio_dashboard_logout_endpoint_returns_redirect_payload(): void
	{
		$admin = $this->makeAdminWithPermissions([
			'page-builder.page.create',
		]);

		$this->actingAs($admin, 'web')
			->postJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/dashboard-logout')
			->assertOk()
			->assertJsonStructure(['redirectTo']);
	}

	public function test_webstudio_trpc_domain_project_returns_virtual_project_without_page_context(): void
	{
		$accessToken = $this->issueEditorToken(['page:read']);

		$this->withHeaders([
			'x-auth-token' => $accessToken,
		])->postJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/trpc/domain.project?batch=1', [
			0 => [
				'projectId' => 'pagify-local',
			],
		])
			->assertOk()
			->assertJsonPath('0.result.data.success', true)
			->assertJsonPath('0.result.data.project.id', 'pagify-local');
	}

	public function test_webstudio_trpc_domain_project_accepts_wrapped_batch_input_shape(): void
	{
		$accessToken = $this->issueEditorToken(['page:read']);

		$this->withHeaders([
			'x-auth-token' => $accessToken,
		])->postJson('/api/v1/' . config('app.admin_url_prefix') . '/page-builder/trpc/domain.project?batch=1', [
			0 => [
				'json' => [
					'projectId' => 'pagify-local',
				],
				'meta' => [
					'values' => [],
				],
			],
		])
			->assertOk()
			->assertJsonPath('0.result.data.success', true)
			->assertJsonPath('0.result.data.project.id', 'pagify-local');
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

	public function test_pages_menu_route_renders_admin_shell_with_embedded_editor_iframe(): void
	{
		$page = Page::query()->create([
			'title' => 'Initial Page',
			'slug' => 'initial-page',
			'status' => 'draft',
			'layout_json' => [],
		]);

		$admin = $this->makeAdminWithPermissions([
			'page-builder.page.viewAny',
		]);

		$response = $this->actingAs($admin, 'web')->get('/admin/page-builder/pages', [
			'X-Inertia' => 'true',
			'X-Requested-With' => 'XMLHttpRequest',
		]);

		$response
			->assertOk()
			->assertHeader('X-Inertia', 'true')
			->assertJsonPath('component', 'PageBuilder/Editor')
			->assertJsonPath('props.editor.iframe.enabled', true);

		$iframeUrl = (string) $response->json('props.editor.iframe.url');

		$this->assertStringStartsWith(route('page-builder.admin.editor.spa'), $iframeUrl);
		$this->assertStringContainsString('accessToken=', $iframeUrl);
		$this->assertStringNotContainsString('authToken=', $iframeUrl);

		$query = parse_url($iframeUrl, PHP_URL_QUERY);
		parse_str(is_string($query) ? $query : '', $params);

		$expectedOrigin = rtrim((string) config('app.url'), '/');

		$this->assertSame($expectedOrigin, $params['parentOrigin'] ?? null);
		$this->assertSame((string) $page->id, $params['pageId'] ?? null);
	}

	public function test_pages_menu_route_bootstraps_virtual_project_when_no_pages_exist(): void
	{
		Page::query()->delete();

		$admin = $this->makeAdminWithPermissions([
			'page-builder.page.viewAny',
		]);

		$response = $this->actingAs($admin, 'web')->get('/admin/page-builder/pages', [
			'X-Inertia' => 'true',
			'X-Requested-With' => 'XMLHttpRequest',
		]);

		$response
			->assertOk()
			->assertHeader('X-Inertia', 'true')
			->assertJsonPath('component', 'PageBuilder/Editor')
			->assertJsonPath('props.editor.iframe.enabled', true);

		$iframeUrl = (string) $response->json('props.editor.iframe.url');

		$this->assertStringStartsWith(route('page-builder.admin.editor.spa'), $iframeUrl);

		$query = parse_url($iframeUrl, PHP_URL_QUERY);
		parse_str(is_string($query) ? $query : '', $params);

		$this->assertSame('pagify-local', $params['pageId'] ?? null);
	}

	public function test_canvas_route_is_available_for_authorized_editor_user(): void
	{
		$admin = $this->makeAdminWithPermissions([
			'page-builder.page.create',
		]);

		$this->actingAs($admin, 'web')
			->get('/' . config('app.admin_url_prefix') . '/page-builder/editor-spa/canvas?accessToken=test-token')
			->assertOk()
			->assertSee('Pagify Webstudio Editor', false);
	}

	public function test_canvas_route_accepts_valid_access_token_without_web_session(): void
	{
		/** @var EditorAccessTokenService $tokenService */
		$tokenService = app(EditorAccessTokenService::class);

		$issued = $tokenService->issue([
			'admin_id' => null,
			'page_id' => null,
			'site_id' => null,
			'theme' => 'default',
			'scopes' => ['page:read'],
		]);

		$accessToken = (string) ($issued['token'] ?? '');

		$this->get('/' . config('app.admin_url_prefix') . '/page-builder/editor-spa/canvas?accessToken=' . urlencode($accessToken))
			->assertOk()
			->assertSee('Pagify Webstudio Editor', false);
	}

	public function test_page_media_usage_is_synced_on_update_and_cleared_on_delete(): void
	{
		$accessToken = $this->issueEditorToken(['page:read', 'page:write']);

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

		$this->withEditorToken($accessToken)->postJson('/api/v1/admin/page-builder/pages', [
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
		])->assertStatus(201);

		$page = Page::query()->where('slug', 'media-sync')->firstOrFail();

		$this->assertTrue(MediaUsage::query()
			->where('context_type', 'page_builder.page')
			->where('context_id', (string) $page->id)
			->where('asset_id', $asset->id)
			->exists());

		$this->withEditorToken($accessToken)->putJson('/api/v1/admin/page-builder/pages/' . $page->id, [
			'title' => 'Media Sync Updated',
			'slug' => 'media-sync',
			'status' => 'draft',
			'layout' => [
				'type' => 'webstudio',
				'webstudio' => [
					'html' => '<main data-pbx-content-slot="true"><p>No media</p></main>',
				],
			],
		])->assertOk();

		$this->assertFalse(MediaUsage::query()
			->where('context_type', 'page_builder.page')
			->where('context_id', (string) $page->id)
			->where('asset_id', $asset->id)
			->exists());

		$this->withEditorToken($accessToken)
			->deleteJson('/api/v1/admin/page-builder/pages/' . $page->id)
			->assertOk();

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

	private function withEditorToken(string $token): self
	{
		return $this->withHeaders([
			'x-auth-token' => $token,
		]);
	}

	/**
	 * @param array<int, string> $scopes
	 */
	private function issueEditorToken(array $scopes, ?int $adminId = null, ?int $pageId = null, ?string $pageSlug = null): string
	{
		/** @var EditorAccessTokenService $tokenService */
		$tokenService = app(EditorAccessTokenService::class);

		$issued = $tokenService->issue([
			'admin_id' => $adminId,
			'page_id' => $pageId,
			'page_slug' => $pageSlug,
			'site_id' => null,
			'site_slug' => null,
			'theme' => 'default',
			'scopes' => $scopes,
		]);

		return (string) ($issued['token'] ?? '');
	}
}
