<?php

$themesBasePath = trim((string) env('ADMIN_THEMES_BASE_PATH', 'themes/admin'), '/');
$activeTheme = trim((string) env('ADMIN_THEME', 'default'), '/');
$fallbackTheme = trim((string) env('ADMIN_THEME_FALLBACK', 'default'), '/');

$themes = array_values(array_unique(array_filter([
    $activeTheme,
    $fallbackTheme,
], static fn (string $theme): bool => $theme !== '')));

$adminPagePaths = array_map(
    static fn (string $theme): string => base_path(sprintf('%s/%s/resources/js/Pages', $themesBasePath, $theme)),
    $themes,
);

return [
    'testing' => [
        'ensure_pages_exist' => true,
        'page_paths' => [
            resource_path('js/Pages'),
            ...$adminPagePaths,
        ],
        'page_extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],
    ],
];
