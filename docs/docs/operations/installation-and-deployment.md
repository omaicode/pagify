---
sidebar_position: 1
title: Installation and Deployment
---

# Installation and Deployment

This guide provides a practical baseline for setting up and deploying Pagify.

## Local installation

From project root:

```bash
composer setup
```

Then start development services:

```bash
composer dev
```

## Pre-deployment checklist

- environment variables are configured correctly
- database migrations are reviewed and ready
- queue worker strategy is defined
- active themes and plugins are validated
- critical test suites pass

## Deployment checklist

1. Pull release code.
2. Install dependencies.
3. Run migrations.
4. Clear and rebuild caches.
5. Restart workers and app processes.
6. Verify health and smoke tests.

Example sequence:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Post-deployment checks

- admin login and dashboard accessible
- page rendering and scheduled publication processing are healthy
- media upload and preview functions are available
- theme rendering and asset URLs are valid

## Rollback readiness

- keep previous release artifact available
- keep backup strategy for database and uploaded assets
- for updater-driven changes, use updater rollback where applicable

## Operational recommendations

- automate queue and scheduler supervision
- monitor logs for permission errors and plugin/theme failures
- run audit cleanup with retention policy regularly

For day-to-day commands, continue with [Runbook](./runbook.md).
