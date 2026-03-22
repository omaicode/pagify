<?php

return [
	'breakpoints' => ['desktop', 'tablet', 'mobile'],

	'editor' => [
		'load_active_theme_styles' => true,
	],

	'webstudio_iframe' => [
		'origin' => (string) env('PAGE_BUILDER_IFRAME_EDITOR_ORIGIN', ''),
		'runtime_mode' => (string) env('PAGE_BUILDER_IFRAME_EDITOR_RUNTIME_MODE', 'upstream-embedded'),
		'upstream_url' => (string) env('PAGE_BUILDER_IFRAME_EDITOR_UPSTREAM_URL', ''),
		'token_ttl_seconds' => (int) env('PAGE_BUILDER_IFRAME_EDITOR_TOKEN_TTL', 3600),
		'token_issuer' => (string) env('PAGE_BUILDER_IFRAME_EDITOR_TOKEN_ISSUER', (string) env('APP_URL', 'pagify')),
		'token_audience' => (string) env('PAGE_BUILDER_IFRAME_EDITOR_TOKEN_AUDIENCE', 'webstudio-editor'),
		'token_secret' => (string) env('PAGE_BUILDER_IFRAME_EDITOR_TOKEN_SECRET', ''),
		'token_replay_protection' => (bool) env('PAGE_BUILDER_IFRAME_EDITOR_TOKEN_REPLAY_PROTECTION', true),
		'token_replay_cache_prefix' => (string) env('PAGE_BUILDER_IFRAME_EDITOR_TOKEN_REPLAY_PREFIX', 'page-builder:editor-token-jti:'),
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
