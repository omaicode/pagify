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
  pages/
    home.json
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
2. `pages/home.json` homepage WSRE document.
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
- `render.engine = wsre`

Important rule: `theme.json.slug` must match folder name.

## WSRE document workflow

### 1. Build page document

Use `pages/home.json` with WSRE nodes:

- `head`: meta/title/script/link nodes
- `body`: HTML-like nodes or dynamic resolvers
- optional `layout_html`: shell containing `{{ head }}` and `{{ content }}` placeholders

Example minimal page:

```json
{
  "version": 1,
  "engine": "wsre",
  "head": [
    { "tag": "title", "text": "My Theme" }
  ],
  "body": [
    {
      "tag": "main",
      "children": [
        { "tag": "h1", "text": "Welcome" }
      ]
    }
  ]
}
```

### 2. Add route-based pages

Start with `pages/home.json`, then add:

- `pages/about.json` for `/about`
- `pages/blog/index.json` for `/blog`

### 3. Add assets

Store assets in:

- `assets/css`
- `assets/js`
- `assets/img`

Use asset URL pattern:

```text
/theme-assets/{theme-slug}/css/app.css
/theme-assets/{theme-slug}/js/app.js
```

### 4. Add localization

Provide translation files:

- `lang/en/theme.php`
- `lang/vi/theme.php`

Localization files can still be consumed by backend/domain services when needed.

## Runtime behavior you must validate

Theme resolution order for frontend rendering:

1. Active theme of current site
2. Fallback theme (`FRONTEND_THEME_FALLBACK`)
3. Default theme (`FRONTEND_THEME`)

If no valid page document is found across fallback chain, runtime will continue with next source and may eventually fall back to page-builder published output.

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
```

6. Run smoke tests and key frontend regression cases.

## Troubleshooting

Theme not listed in manager:

- check `theme.json` existence and validity
- check slug-folder mismatch

Theme selected but not rendering:

- check `pages/home.json`
- check fallback/default theme validity
- clear framework cache

Asset file changes not visible:

- confirm correct path under `assets/*`
- clear cache and verify cache-busting strategy

## Related references

- [Theme Customization](./theme-customization.md)
- [Theme Development Contract](../../reference/theme-development-contract.md)
- [Runbook](../../operations/runbook.md)
