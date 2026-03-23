---
sidebar_position: 3
title: Theme Development Contract
---

# Development Contract

Last updated: 2026-03-23

This document defines the official contract for community frontend themes in Pagify.

## Scope

Frontend themes are stored at:

- `themes/main/{THEME_NAME}`

This contract applies to:

- Theme discovery in admin Theme Manager
- Theme activation per site
- Public page rendering fallback chain

## Directory Structure

A valid theme MUST follow this baseline structure:

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
    en/
      theme.php
    vi/
      theme.php
```

## Manifest Contract: `theme.json`

`theme.json` is mandatory.

### Required fields

- `slug` string, max 120, regex: `^[a-z0-9]+(?:-[a-z0-9]+)*$`
- `name` string, max 255
- `version` string, max 64

### Optional fields

- `description` string
- `author` string, max 255
- `requires` object
- `supports` object
- `render` object with `engine` set to `wsre`

### Example

```json
{
  "slug": "default",
  "name": "Default Main Theme",
  "version": "1.0.0",
  "description": "Default frontend theme for Pagify public pages.",
  "author": "Pagify",
  "render": {
    "engine": "wsre"
  },
  "requires": {
    "php": ">=8.2",
    "laravel": "^12.0"
  },
  "supports": {
    "pagify": "^1.0"
  }
}
```

## Runtime Behavior

### Activation scope

- Active theme is resolved per site via setting key: `theme.main.active`.
- Admin can activate a theme for a target site.

### Fallback chain

When rendering public pages, Pagify resolves theme view paths in order:

1. Active theme for current site
2. Config fallback theme (`FRONTEND_THEME_FALLBACK`)
3. Config default theme (`FRONTEND_THEME`)

A theme is considered runtime-usable only when:

- Theme directory exists
- `theme.json` exists and passes validation
- `theme.json.slug` matches directory name
- `render.engine` is `wsre`
- `pages/home.json` exists

If no usable page document is found after fallback chain, Pagify continues to fallback runtime sources (including page-builder published output).

## Business Rules

- Default theme cannot be deleted (`THEME_LOCKED`).
- A theme currently active on any site cannot be deleted (`THEME_IN_USE`).
- Invalid manifest themes cannot be activated (`THEME_INVALID`).

## UX Requirements for Theme Manager

Theme Manager should clearly show:

- Manifest validity
- Current-site active status
- Usage count and active sites
- Delete eligibility and reason when disabled

## Compatibility and Safe Updates

Theme authors should:

- Keep `theme.json.version` semantic and updated
- Avoid removing `pages/home.json`
- Test fallback behavior after changes
- Document breaking changes in `README.md`

## Troubleshooting Checklist

1. Theme not listed in manager:
- Check `theme.json` exists and valid JSON
- Ensure `slug` matches folder name

2. Theme cannot activate:
- Check manifest errors shown in manager
- Verify target site exists and active

3. Theme selected but not rendered:
- Confirm `pages/home.json` exists
- Verify fallback theme/default theme manifests are valid
- Clear cache: `php artisan optimize:clear`

## Theme Asset Exposure

Frontend theme static assets are served from:

- Source folder: `themes/main/{THEME}/assets/{js,css,img}`
- Public URL pattern: `/theme-assets/{THEME}/{path}`

Security rules:

- Only `js`, `css`, `img` top-level directories are allowed.
- Path traversal (`..`) is blocked.
- Theme manifest must be valid and slug must match directory.

Use in WSRE document nodes via plain URLs:

```json
{
  "tag": "link",
  "attrs": {
    "rel": "stylesheet",
    "href": "/theme-assets/default/css/app.css"
  }
}
```

Cache busting:

- Theme asset URLs support configurable strategies via `core.frontend_ui.assets.cache_busting.strategy`.
- Supported values:
  - `mtime` (default): `?v={filemtime}`
  - `hash`: `?v={sha1_file}` (truncated by `hash_length`)
  - `off`: do not append version query
- Config keys:
  - `FRONTEND_THEME_ASSET_CACHE_BUSTING` (`mtime|hash|off`)
  - `FRONTEND_THEME_ASSET_HASH_LENGTH` (default `12`)

## WSRE Dynamic Extensions

For runtime dynamic rendering, use WSRE resolver nodes and register resolvers via core WSRE resolver registry/hooks.
