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

## Media admin APIs

- `/media/assets`
- `/media/folders`
- `/media/upload-sessions`

## Page builder admin APIs

Primary page builder APIs:

- `/page-builder/editor/access-token`
- `/page-builder/pages`
- `/page-builder/pages/{page}`
- `/page-builder/pages/{page}/publish`

Webstudio compatibility APIs:

- `/page-builder/data/{projectId}`
- `/page-builder/patch`
- `/page-builder/resources-loader`
- `/page-builder/assets`
- `/page-builder/assets/{name}`
- `/page-builder/assets/{assetId}`
- `/page-builder/trpc/{path?}`
- `/page-builder/dashboard-logout`

Supporting public compatibility routes:

- `/cgi/image/{path?}`
- `/cgi/asset/{path?}`

## Updater admin APIs

- `/updater/executions`
- `/updater/executions/dry-run`
- `/updater/executions/module/{module}`
- `/updater/executions/all`
- `/updater/executions/{execution}/rollback`

## Notes

- All admin APIs require authenticated admin context or a valid editor token with the relevant scope.
- Webstudio compatibility routes are part of the page-builder integration layer and are page-scoped in practice.
- API contracts may evolve with module upgrades; use feature tests as the source of truth for behavior.
