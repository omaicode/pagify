---
sidebar_position: 2
title: Artisan Commands
---

# Artisan Commands

This page lists the most relevant Pagify-specific commands for daily operations.

## CMS scaffolding and theme tooling

- `cms:make-plugin`
- `cms:make-theme`
- `cms:theme:clear-cache`

## Core operations

- `core:audit:cleanup`

## Module and package lifecycle

- `modules:list`
- `modules:sync`
- `modules:cache`
- `modules:clear`

## Updater operations

- `updater:module`
- `updater:all`
- `updater:rollback`

## Common Laravel operations used in Pagify workflows

- `php artisan migrate`
- `php artisan db:seed`
- `php artisan queue:work`
- `php artisan optimize:clear`
- `php artisan test`

## Recommended command sequence for local maintenance

1. `php artisan optimize:clear`
2. `php artisan migrate`
3. `php artisan queue:work --queue=default --tries=3`
4. `php artisan test`

For environment-specific workflows, see [Runbook](../operations/runbook.md).
