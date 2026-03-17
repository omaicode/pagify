---
sidebar_position: 5
title: Plugin Development
---

# Plugin Development

This guide describes how to design, scaffold, implement, and operate plugins in Pagify.

## What a plugin is in Pagify

A plugin is a deployable extension package under `plugins/{slug}` (or a Composer package with `plugin.json` at package root) that can contribute:

- service providers
- hooks/subscribers
- permissions
- migrations
- extension points (field types, blocks, dashboard widgets, automation actions, menu items)

Plugin lifecycle is managed from Admin and API (`/{admin_prefix}/plugins`, `api/v1/{admin_prefix}/plugins`).

## Scaffold a new plugin

From project root:

```bash
php artisan cms:make-plugin "My Plugin"
```

This command creates `plugins/my-plugin` with:

- `plugin.json`
- `src/Providers/PluginServiceProvider.php`
- `database/migrations/`
- `resources/js/`
- `config/`
- `README.md`

## Manifest contract (`plugin.json`)

Minimum keys you should maintain:

- `slug`
- `name`
- `version`
- `description`
- `requires` (`php`, `laravel`, `core`)
- `providers`

Pagify scaffold also includes optional sections for:

- `hooks.subscribers`
- `ui.menu`
- `permissions`
- `migrations`
- `extension_points`

Example:

```json
{
  "slug": "my-plugin",
  "name": "My Plugin",
  "version": "0.1.0",
  "description": "Plugin description",
  "requires": {
    "php": ">=8.2",
    "laravel": "^12.0",
    "core": "^1.0"
  },
  "providers": [
    "Plugins\\MyPlugin\\Providers\\PluginServiceProvider"
  ],
  "hooks": {
    "subscribers": []
  },
  "permissions": [
    "plugin.my.plugin.manage"
  ],
  "extension_points": {
    "field_types": [],
    "blocks": [],
    "dashboard_widgets": [],
    "automation_actions": [],
    "menu_items": []
  }
}
```

## Implement service provider

Use `src/Providers/PluginServiceProvider.php` to register container bindings, event listeners, and boot-time integration.

Recommended practice:

- keep `register()` for bindings/config merge
- keep `boot()` for route/view/publish/event wiring
- avoid heavy IO in `boot()` to keep request startup fast

## Hook integration

Plugins can subscribe to platform hooks and extend behaviors, including theme helper injection (`theme.render.helpers`).

See related guide for hook return contract and subscriber example:

- [Theme Development Contract](../../reference/theme-development-contract.md)

## Database and migrations

When your plugin needs schema changes:

1. Add migrations under `plugins/{slug}/database/migrations`
2. Ensure migration paths are declared/loaded by plugin boot logic
3. Run:

```bash
php artisan migrate
```

If plugin APIs report state initialization issues, migrate first.

## Local development workflow

Typical sequence:

```bash
php artisan optimize:clear
php artisan migrate
php artisan queue:work --queue=default --tries=3
php artisan test
```

Useful references:

- [Runbook](../../operations/runbook.md)
- [Core Module](../../modules/core-module.md)
- [Artisan Commands](../../reference/artisan-commands.md)

## Packaging and installation notes

Pagify plugin manager expects `plugin.json` at plugin/package root:

- local plugin folder: `plugins/{slug}/plugin.json`
- Composer package: `vendor/{vendor}/{package}/plugin.json`
- ZIP upload: must contain `plugin.json` in extracted plugin root

If `plugin.json` is missing or invalid, installation will fail.

## Safety and operations

- Keep plugin logic isolated and idempotent.
- Validate permission checks for every admin/API action.
- If a plugin crashes in runtime, safe mode can auto-disable it and return `PLUGIN_SAFE_MODE_ENABLED` on affected paths.
- Re-enable plugin after fix and verify with regression tests.

## Release checklist

1. Validate `plugin.json` metadata and version.
2. Run migration and rollback checks.
3. Verify enable/disable/uninstall flow in Admin.
4. Run feature tests for integrated module paths.
5. Document plugin-specific env vars and operational notes.
