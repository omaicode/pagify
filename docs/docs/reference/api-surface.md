---
sidebar_position: 1
title: API Surface
---

# API Surface

This page summarizes the primary API groups exposed by Pagify.

Looking for class and method level references? Open [API Reference](/api-reference).

## Public APIs

### Health

- `GET /api/v1/health`

### Content delivery

- `GET /api/v1/content/health`
- `GET /api/v1/content/{contentTypeSlug}`
- `GET /api/v1/content/{contentTypeSlug}/{entrySlug}`

## Admin APIs

All admin APIs are prefixed by:

- `/api/v1/{admin_prefix}`

## Core admin APIs

- `/tokens`
- `/permissions`
- `/admin-groups`
- `/admins`
- `/profile`
- `/modules`
- `/plugins`
- `/themes`

## Content admin APIs

- `/content/health`
- `/content/{contentTypeSlug}/relations/picker`

## Media admin APIs

- `/media/assets`
- `/media/folders`
- `/media/upload-sessions`

## Page builder admin APIs

- `/page-builder/registry`
- `/page-builder/templates`
- `/page-builder/sections`

## Updater admin APIs

- `/updater/executions`
- `/updater/executions/dry-run`
- `/updater/executions/module/{module}`
- `/updater/executions/all`
- `/updater/executions/{execution}/rollback`

## Notes

- All admin APIs require authenticated admin context and relevant permissions.
- API contracts may evolve with module upgrades; use feature tests as the source of truth for behavior.
