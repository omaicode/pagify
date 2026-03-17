---
sidebar_position: 1
title: Quickstart
---

# Quickstart

This guide helps you run Pagify locally in the shortest reliable path.

## 1. Prerequisites

Make sure your machine has:

- PHP 8.2+
- Composer
- Node.js 20+ and npm 10+
- MySQL or PostgreSQL

## 2. Install and bootstrap

From the project root:

```bash
composer setup
```

What this does:

- installs PHP dependencies
- creates `.env` if missing
- generates app key
- runs migrations
- installs frontend dependencies for the active admin theme
- builds frontend assets

## 3. Start development services

From the project root:

```bash
composer dev
```

This starts the app server, queue worker, logs watcher, and Vite dev server.

## 4. Verify installation

1. Open the app in your browser.
2. Open the admin login page.
3. Sign in with your configured admin credentials.
4. Run backend tests to confirm health:

```bash
php artisan test
```

## 5. Useful first commands

Run queue worker only:

```bash
php artisan queue:work --queue=default --tries=3
```

Clear caches:

```bash
php artisan optimize:clear
```

Rebuild frontend assets:

```bash
composer setup
```

## 6. Common issues

### Vite manifest missing

Re-run:

```bash
composer setup
```

### Schema jobs stay queued

Ensure queue worker is running:

```bash
php artisan queue:work --queue=default --tries=3
```

### Login fails in local environment

Reseed core admin account:

```bash
php artisan db:seed --class="Pagify\\Core\\Database\\Seeders\\CoreDatabaseSeeder"
```

Next step: continue with [Learning Path](./learning-path.md) to choose your track.
