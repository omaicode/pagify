---
sidebar_position: 4
title: Theme Customization
---

# Theme Customization

This guide covers production-safe customization for Pagify frontend themes.

## Before you start

Read the [Theme Development Contract](../../reference/theme-development-contract.md) first.

A valid theme must include:

- `theme.json`
- `pages/home.json`
- static assets in `assets/js`, `assets/css`, `assets/img`

## Safe customization workflow

1. Create a feature branch.
2. Validate `theme.json` structure and slug.
3. Apply WSRE document updates in small commits.
4. Verify fallback behavior with invalid/missing files.
5. Run cache clear and re-test rendering.

## Common customization tasks

### 1. Change homepage layout

Edit:

- `themes/main/{THEME_NAME}/pages/home.json`

Optional shell control:

- `layout_html` field inside WSRE page document

### 2. Add new styles and scripts

Place files in:

- `themes/main/{THEME_NAME}/assets/css`
- `themes/main/{THEME_NAME}/assets/js`

Use theme asset endpoint:

```text
/theme-assets/{THEME_NAME}/css/app.css
/theme-assets/{THEME_NAME}/js/app.js
```

### 3. Add localized theme text

Add translation entries:

- `themes/main/{THEME_NAME}/lang/en/theme.php`
- `themes/main/{THEME_NAME}/lang/vi/theme.php`

You can map localized values from backend/domain data into WSRE output when needed.

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

- verify `pages/home.json` exists
- verify fallback theme and default theme are valid
- clear cache:

```bash
php artisan optimize:clear
```

## Operational recommendation

Treat theme updates like backend updates:

- small scoped changes
- explicit rollback path
- test in staging before production activation
