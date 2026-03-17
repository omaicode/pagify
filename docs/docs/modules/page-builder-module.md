---
sidebar_position: 4
title: Page Builder Module
---

# Page Builder Module

The Page Builder module provides admin tooling for composing and publishing pages.

## Responsibilities

- Page CRUD in admin interface
- Page preview and publish actions
- Revision history and rollback
- Section and template library persistence
- Registry endpoints for builder capabilities

## Key admin routes

- `/{admin_prefix}/page-builder/pages`
- `/{admin_prefix}/page-builder/pages/create`
- `/{admin_prefix}/page-builder/pages/{page}/edit`
- `/{admin_prefix}/page-builder/pages/{page}/preview`
- `/{admin_prefix}/page-builder/pages/{page}/revisions`

## Key API groups

- `api/v1/{admin_prefix}/page-builder/registry`
- `api/v1/{admin_prefix}/page-builder/templates`
- `api/v1/{admin_prefix}/page-builder/sections`

## Lifecycle

1. Create page draft.
2. Compose with sections/templates.
3. Preview in admin.
4. Publish and maintain revisions.

## Testing coverage highlights

- page builder lifecycle
- page builder theme render integration
