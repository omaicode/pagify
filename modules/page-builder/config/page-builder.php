<?php

return [
	'breakpoints' => ['desktop', 'tablet', 'mobile'],

	'editor' => [
		'simplified_mode' => true,
		'load_active_theme_styles' => true,
		'primary_blocks' => [
			'hero-banner',
			'columns-2',
			'image',
			'paragraph',
			'button',
			'stats-row',
			'feature-checklist',
			'logo-cloud',
			'cta-panel',
			'pricing-table',
			'contact-form',
		],
	],

	'webstudio_iframe' => [
		'origin' => (string) env('PAGE_BUILDER_IFRAME_EDITOR_ORIGIN', ''),
		'runtime_mode' => (string) env('PAGE_BUILDER_IFRAME_EDITOR_RUNTIME_MODE', 'upstream-embedded'),
		'upstream_url' => (string) env('PAGE_BUILDER_IFRAME_EDITOR_UPSTREAM_URL', 'https://app.webstudio.is'),
		'token_ttl_seconds' => (int) env('PAGE_BUILDER_IFRAME_EDITOR_TOKEN_TTL', 300),
		'token_issuer' => (string) env('PAGE_BUILDER_IFRAME_EDITOR_TOKEN_ISSUER', (string) env('APP_URL', 'pagify')),
		'token_audience' => (string) env('PAGE_BUILDER_IFRAME_EDITOR_TOKEN_AUDIENCE', 'webstudio-editor'),
		'token_secret' => (string) env('PAGE_BUILDER_IFRAME_EDITOR_TOKEN_SECRET', ''),
		'token_replay_protection' => (bool) env('PAGE_BUILDER_IFRAME_EDITOR_TOKEN_REPLAY_PROTECTION', true),
		'token_replay_cache_prefix' => (string) env('PAGE_BUILDER_IFRAME_EDITOR_TOKEN_REPLAY_PREFIX', 'page-builder:editor-token-jti:'),
	],

	'webstudio_iframe_contract' => [
		'protocol_version' => 1,
		'namespace' => 'pagify:editor',
		'messages' => [
			'parent_to_child' => [
				'init' => 'pagify:editor:init',
				'set_layout' => 'pagify:editor:set-layout',
				'flush' => 'pagify:editor:flush',
				'search' => 'pagify:editor:search',
				'token_refresh_result' => 'pagify:editor:token-refresh-result',
			],
			'child_to_parent' => [
				'ready' => 'pagify:editor:ready',
				'error' => 'pagify:editor:error',
				'layout_change' => 'pagify:editor:layout-change',
				'token_refresh_request' => 'pagify:editor:token-refresh-request',
			],
			'host_events' => [
				'flush_request' => 'pbx-editor-flush',
				'search_request' => 'pbx-editor-search:set',
			],
		],
	],

	'permissions' => [
		'page-builder.page.viewAny',
		'page-builder.page.view',
		'page-builder.page.create',
		'page-builder.page.update',
		'page-builder.page.delete',
		'page-builder.page.publish',
	],
];
