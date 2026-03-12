# Pagify Theme Development Contract

Last updated: 2026-03-12

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
  resources/
    views/
      layouts/
        app.blade.php
      pages/
        page.blade.php
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

### Example

```json
{
  "slug": "default",
  "name": "Default Main Theme",
  "version": "1.0.0",
  "description": "Default frontend theme for Pagify public pages.",
  "author": "Pagify",
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

If no usable `pages/page.blade.php` is found after fallback chain, Pagify returns the raw page snapshot HTML.

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
- Avoid removing `resources/views/pages/page.blade.php`
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
- Confirm `resources/views/pages/page.blade.php` exists
- Verify fallback theme/default theme manifests are valid
- Clear cache: `php artisan optimize:clear`
