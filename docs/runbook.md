# Pagify runbook

Last updated: 2026-03-04

## Scope

This runbook standardizes local setup and daily development commands.

Admin theme is configurable by environment variables:

- `ADMIN_THEMES_BASE_PATH` (default: `themes/admin`)
- `ADMIN_THEME` (default: `default`)
- `ADMIN_THEME_FALLBACK` (default: `default`)

Default admin UI path is `themes/admin/default`. If you switch to `ADMIN_THEME=v2`, the app will load views and frontend tooling from `themes/admin/v2`.

## Prerequisites

- PHP 8.2+
- Composer
- Node.js 20+
- npm 10+
- MySQL/PostgreSQL (or your configured DB)

## First-time setup

From project root:

```bash
composer setup
```

This command will:

- install PHP dependencies
- create `.env` if missing
- generate app key
- run migrations
- install frontend dependencies in `themes/admin/${ADMIN_THEME:-default}`
- build frontend assets

## Daily development

### Start app + queue + logs + Vite dev server

From project root:

```bash
composer dev
```

### Run backend tests

From project root:

```bash
php artisan test
```

### Build frontend assets

From project root:

```bash
ADMIN_THEME=v2 composer setup
```

### Run frontend dev server only

From theme workspace:

```bash
cd themes/admin/${ADMIN_THEME:-default}
npm run dev
```

Example for `v2`:

```bash
export ADMIN_THEME=v2
composer dev
```

## Key paths

- Active admin theme root: `${ADMIN_THEMES_BASE_PATH}/${ADMIN_THEME}`
- Admin Vite config: `${ADMIN_THEMES_BASE_PATH}/${ADMIN_THEME}/vite.config.js`
- Admin JS entry: `${ADMIN_THEMES_BASE_PATH}/${ADMIN_THEME}/resources/js/admin.js`
- Admin frontend translations: `${ADMIN_THEMES_BASE_PATH}/${ADMIN_THEME}/lang/{locale}/ui.php`
- Admin root Inertia view: `${ADMIN_THEMES_BASE_PATH}/${ADMIN_THEME}/resources/views/admin/app.blade.php`
- Admin login page (Vue): `${ADMIN_THEMES_BASE_PATH}/${ADMIN_THEME}/resources/js/Pages/Admin/Auth/Login.vue`

## Troubleshooting

- If admin UI is not updated, rebuild assets from active theme path.
- If tests fail with missing Vite manifest entry, run `composer setup` with correct `ADMIN_THEME`.
- If active theme path does not exist, app falls back to `ADMIN_THEME_FALLBACK`.
- If login always shows invalid credentials, reseed core admin account:

```bash
php artisan db:seed --class="Modules\\Core\\Database\\Seeders\\CoreDatabaseSeeder"
```

- Default dev admin credentials can be overridden via env:
	- `CORE_ADMIN_USERNAME` (default: `admin`)
	- `CORE_ADMIN_EMAIL` (default: `admin@localhost`)
	- `CORE_ADMIN_PASSWORD` (default: `password`)
- If config/view cache causes stale behavior, clear Laravel caches:

```bash
php artisan optimize:clear
```
