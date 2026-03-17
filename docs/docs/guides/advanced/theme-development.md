---
sidebar_position: 3
title: Theme Development
---

# Theme Development

This guide is the practical entry point for building and maintaining frontend themes in Pagify.

For strict schema and runtime rules, see the contract:

- [Theme Development Contract](../../reference/theme-development-contract.md)

## Theme location and baseline

A frontend theme lives under:

- `themes/main/{theme-slug}`

Minimum structure:

```text
themes/main/{slug}/
  theme.json
  README.md
  assets/
    js/
    css/
    img/
  layouts/
    app.twig
  pages/
    home.twig
  lang/
    en/theme.php
    vi/theme.php
```

## Start from a new theme

Create a theme scaffold:

```bash
php artisan cms:make-theme "My Theme"
```

Then review and update:

1. `theme.json` metadata and version.
2. `layouts/app.twig` layout shell.
3. `pages/home.twig` homepage content.
4. Assets and localization files.

## Manifest essentials (`theme.json`)

Required fields:

- `slug`
- `name`
- `version`

Recommended fields:

- `description`
- `author`
- `requires`
- `supports`
- `render.engine = twig`

Important rule: `theme.json.slug` must match folder name.

## Template development workflow

### 1. Build layout shell

Use `layouts/app.twig` for shared page structure:

- metadata
- header/footer
- global CSS/JS
- blocks or includes used by pages

### 2. Build page templates

Start with `pages/home.twig`, then expand to additional page templates if your routing/render stack supports them.

### 3. Add assets

Store assets in:

- `assets/css`
- `assets/js`
- `assets/img`

Load assets with helper:

```twig
<link rel="stylesheet" href="{{ asset_url('css/app.css') }}">
<script defer src="{{ asset_url('js/app.js') }}"></script>
```

### 4. Add localization

Provide translation files:

- `lang/en/theme.php`
- `lang/vi/theme.php`

Use helper in Twig:

```twig
{{ t('theme.hero_title') }}
```

## Runtime behavior you must validate

Theme resolution order for frontend rendering:

1. Active theme of current site
2. Fallback theme (`FRONTEND_THEME_FALLBACK`)
3. Default theme (`FRONTEND_THEME`)

If templates are missing/invalid across fallback chain, runtime may render snapshot HTML fallback.

## Extension points and helpers

Themes can consume platform helpers and plugin-injected helpers.

For helper-hook contract and subscriber example:

- [Theme Development Contract](../../reference/theme-development-contract.md)
- [Plugin Development](./plugin-development.md)

## QA and release checklist

1. Validate `theme.json` syntax and slug matching.
2. Activate theme in Admin and verify render on target site.
3. Verify fallback behavior by simulating missing template/invalid manifest.
4. Validate all `asset_url` references and cache-busting output.
5. Clear caches and retest:

```bash
php artisan optimize:clear
php artisan cms:theme:clear-cache
```

6. Run smoke tests and key frontend regression cases.

## Troubleshooting

Theme not listed in manager:

- check `theme.json` existence and validity
- check slug-folder mismatch

Theme selected but not rendering:

- check `pages/home.twig`
- check fallback/default theme validity
- clear framework/theme cache

Asset file changes not visible:

- confirm correct path under `assets/*`
- clear cache and verify cache-busting strategy

## Related references

- [Theme Customization](./theme-customization.md)
- [Theme Development Contract](../../reference/theme-development-contract.md)
- [Runbook](../../operations/runbook.md)
