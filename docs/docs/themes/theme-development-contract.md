# Development Contract

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
  assets/
    js/
    css/
    img/
  layouts/
    app.twig
  pages/
    home.twig
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
- `render` object with `engine` set to `twig`

### Example

```json
{
  "slug": "default",
  "name": "Default Main Theme",
  "version": "1.0.0",
  "description": "Default frontend theme for Pagify public pages.",
  "author": "Pagify",
  "render": {
    "engine": "twig"
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

If no usable `pages/home.twig` is found after fallback chain, Pagify returns the raw page snapshot HTML.

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
- Avoid removing `pages/home.twig`
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
- Confirm `pages/home.twig` exists
- Verify fallback theme/default theme manifests are valid
- Clear cache: `php artisan optimize:clear`

## Twig Helper API

Themes can use both global helpers and namespace helpers:

- Global functions:
  - `asset_url(path)`
  - `page_url(slug)`
  - `site_url(path)`
  - `t(key, replacements = {})`
  - `format_date(value, format = 'Y-m-d H:i')`
  - `setting(key, default = null)`
- Namespace helpers:
  - `helpers.asset.url(path)`
  - `helpers.url.page(slug)`
  - `helpers.url.site(path)`
  - `helpers.i18n.t(key, replacements = {})`
  - `helpers.format.date(value, format)`
  - `helpers.settings.get(key, default)`

Twig sandbox is enabled by default for community-safety and only allows a strict helper whitelist.

## Theme Asset Exposure

Frontend theme static assets are served from:

- Source folder: `themes/main/{THEME}/assets/{js,css,img}`
- Public URL pattern: `/theme-assets/{THEME}/{path}`

Security rules:

- Only `js`, `css`, `img` top-level directories are allowed.
- Path traversal (`..`) is blocked.
- Theme manifest must be valid and slug must match directory.

Use in Twig:

```twig
<link rel="stylesheet" href="{{ asset_url('css/app.css') }}">
<script defer src="{{ asset_url('js/app.js') }}"></script>
<img src="{{ asset_url('img/logo.png') }}" alt="logo">
```

`asset_url(path)` resolves to the active theme for the current site by default.

Cache busting:

- `asset_url(path)` supports configurable strategies via `core.frontend_ui.assets.cache_busting.strategy`.
- Supported values:
  - `mtime` (default): `?v={filemtime}`
  - `hash`: `?v={sha1_file}` (truncated by `hash_length`)
  - `off`: do not append version query
- Config keys:
  - `FRONTEND_THEME_ASSET_CACHE_BUSTING` (`mtime|hash|off`)
  - `FRONTEND_THEME_ASSET_HASH_LENGTH` (default `12`)

## Extension Hook Spec: `theme.render.helpers`

Pagify exposes a hook for plugins/modules to inject extra Twig helper functions:

- Hook name: `theme.render.helpers`
- Dispatch timing: each render call before template evaluation
- Dispatcher: `Pagify\\Core\\Services\\EventBus::emitHook()`
- Consumer: `Pagify\\Core\\Services\\ThemeHelperRegistry`

### Return contract per hook listener

Each listener should return an array with this structure:

```php
[
  'global' => [
    'helper_name' => callable,
  ],
]
```

Rules:

- `global` is optional; when present it must be an associative array.
- Key must be non-empty string (Twig function name).
- Value must be callable.
- Invalid entries are ignored silently.
- Name collision strategy: last registered listener wins and overrides previous helper name.

### Example plugin subscriber

```php
<?php

namespace Plugins\\Acme\\Hooks;

use Pagify\\Core\\Contracts\\CoreHookSubscriber;
use Pagify\\Core\\Services\\EventBus;

class ThemeHelperSubscriber implements CoreHookSubscriber
{
  public function register(EventBus $eventBus): void
  {
    $eventBus->onHook('theme.render.helpers', function (): array {
      return [
        'global' => [
          'excerpt' => static function (?string $text, int $limit = 120): string {
            $text = trim((string) $text);

            return mb_strlen($text) <= $limit
              ? $text
              : rtrim(mb_substr($text, 0, $limit)).'...';
          },
        ],
      ];
    });
  }
}
```

Then use in Twig:

```twig
<p>{{ excerpt(page.title, 40) }}</p>
```

### Security notes

- Hook-injected helpers are still subject to Twig sandbox policy.
- Only helpers that become part of the engine allowlist are callable in templates.
- Avoid exposing file-system, process, or raw shell/network operations via helpers.
- Keep helpers pure and deterministic when possible for safer caching and debugging.
