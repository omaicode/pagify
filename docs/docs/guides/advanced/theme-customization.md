---
sidebar_position: 2
title: Theme Customization
---

# Theme Customization

This guide covers production-safe customization for Pagify frontend themes.

## Before you start

Read the [Theme Development Contract](../../themes/theme-development-contract.md) first.

A valid theme must include:

- `theme.json`
- `layouts/app.twig`
- `pages/home.twig`
- static assets in `assets/js`, `assets/css`, `assets/img`

## Safe customization workflow

1. Create a feature branch.
2. Validate `theme.json` structure and slug.
3. Apply Twig/layout updates in small commits.
4. Verify fallback behavior with invalid/missing files.
5. Run cache clear and re-test rendering.

## Common customization tasks

### 1. Change homepage layout

Edit:

- `themes/main/{THEME_NAME}/pages/home.twig`
- `themes/main/{THEME_NAME}/layouts/app.twig`

### 2. Add new styles and scripts

Place files in:

- `themes/main/{THEME_NAME}/assets/css`
- `themes/main/{THEME_NAME}/assets/js`

Use Twig helper:

```twig
<link rel="stylesheet" href="{{ asset_url('css/app.css') }}">
<script defer src="{{ asset_url('js/app.js') }}"></script>
```

### 3. Add localized theme text

Add translation entries:

- `themes/main/{THEME_NAME}/lang/en/theme.php`
- `themes/main/{THEME_NAME}/lang/vi/theme.php`

Use helper:

```twig
{{ t('theme.hero_title') }}
```

## Validation checklist before merge

- `theme.json` is valid JSON and slug matches directory
- homepage renders with active theme
- fallback theme works when active theme is invalid
- no broken asset URLs
- cache cleared and re-verified

## Troubleshooting

Theme not listed in manager:

- verify `theme.json` exists and is valid
- verify slug matches folder name

Theme selected but not rendered:

- verify `pages/home.twig` exists
- verify fallback theme and default theme are valid
- clear cache:

```bash
php artisan optimize:clear
php artisan cms:theme:clear-cache
```

## Operational recommendation

Treat theme updates like backend updates:

- small scoped changes
- explicit rollback path
- test in staging before production activation
